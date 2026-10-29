<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use App\Service\JwtTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MfaFlowTest extends WebTestCase
{
    private const MFA_EMAIL = 'mfa.user@example.com';
    private const MFA_PASSWORD = 'Mfa#User123456';

    /** Upper bound for the rate limiter test; config/packages/rate_limiter.yaml sets login limit 100 in the test env. */
    private const MAX_LOGIN_ATTEMPTS = 150;

    private string $mfaSecret;

    private function createPreparedClient(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();
        $this->resetDatabase();

        return $client;
    }

    public function testLoginWithMfaEnabledRequiresSecondFactor(): void
    {
        $payload = $this->loginMfaUser($this->createPreparedClient());

        $this->assertTrue($payload['mfa_required']);
        $this->assertNull($payload['access_token']);
        $this->assertNotEmpty($payload['token']);
    }

    public function testMfaVerifyWithValidTotpReturnsAccessToken(): void
    {
        $client = $this->createPreparedClient();
        $login = $this->loginMfaUser($client);

        $this->verifyMfa($client, $login['token'], $this->totpCode($this->mfaSecret, time()));

        $this->assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($payload['access_token']);
        $this->assertNotEmpty($payload['refresh_token']);
        $this->assertSame(self::MFA_EMAIL, $payload['user']['email']);

        $client->request(
            'GET',
            '/api/auth/me',
            server: ['HTTP_AUTHORIZATION' => 'Bearer '.$payload['access_token']]
        );
        $this->assertResponseIsSuccessful();
    }

    public function testMfaVerifyRejectsReplayOfUsedMfaToken(): void
    {
        $client = $this->createPreparedClient();
        $login = $this->loginMfaUser($client);
        $code = $this->totpCode($this->mfaSecret, time());

        $this->verifyMfa($client, $login['token'], $code);
        $this->assertResponseIsSuccessful();

        $this->verifyMfa($client, $login['token'], $code);
        $this->assertResponseStatusCodeSame(401);
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('access_token', $payload);
    }

    public function testMfaVerifyRejectsAccessTokenInPlaceOfMfaToken(): void
    {
        $client = $this->createPreparedClient();
        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'admin@example.com', 'password' => 'Admin#123456'], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseIsSuccessful();
        $accessToken = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['access_token'];

        $this->verifyMfa($client, $accessToken, $this->totpCode($this->mfaSecret, time()));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMfaVerifyRejectsSignedTokenOfAnotherType(): void
    {
        $client = $this->createPreparedClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $mfaUser = $em->getRepository(User::class)->findOneBy(['email' => self::MFA_EMAIL]);
        $this->assertNotNull($mfaUser);

        // Same signing key and user as an MFA-pending token, but a different "type" claim.
        $otherToken = static::getContainer()->get(JwtTokenService::class)->generateRefreshToken($mfaUser);

        $this->verifyMfa($client, $otherToken, $this->totpCode($this->mfaSecret, time()));

        $this->assertResponseStatusCodeSame(401);
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('access_token', $payload);
    }

    public function testMfaVerifyRejectsInvalidCodeAndCountsFailure(): void
    {
        $client = $this->createPreparedClient();
        $login = $this->loginMfaUser($client);

        $this->verifyMfa($client, $login['token'], $this->invalidCode());

        $this->assertResponseStatusCodeSame(401);
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('access_token', $payload);

        $client->request('GET', '/api/metrics');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('app_auth_mfa_failed_total 1', $client->getResponse()->getContent());
    }

    public function testRepeatedFailedLoginsFromOneIpAreRateLimited(): void
    {
        $client = $this->createPreparedClient();
        // Unique documentation-range address per run: limiter state is kept in the
        // test cache pool between runs, so a fixed IP could start pre-consumed.
        $attackerIp = sprintf('2001:db8::%x:%x', random_int(1, 0xffff), random_int(1, 0xffff));

        $limitedAt = null;
        for ($attempt = 1; $attempt <= self::MAX_LOGIN_ATTEMPTS; ++$attempt) {
            $this->postLogin($client, 'unknown.'.$attempt.'@example.com', 'wrong-password', $attackerIp);
            $status = $client->getResponse()->getStatusCode();
            if ($status === 429) {
                $limitedAt = $attempt;
                break;
            }
            $this->assertSame(401, $status, sprintf('Attempt %d before the limit should be rejected as 401', $attempt));
        }

        $this->assertNotNull($limitedAt, sprintf('No HTTP 429 within %d failed logins', self::MAX_LOGIN_ATTEMPTS));
        $this->assertGreaterThan(1, $limitedAt);

        // Valid credentials from the limited IP are still refused ...
        $this->postLogin($client, 'admin@example.com', 'Admin#123456', $attackerIp);
        $this->assertResponseStatusCodeSame(429);

        // ... while another client IP is unaffected.
        $this->postLogin($client, 'admin@example.com', 'Admin#123456', sprintf('2001:db8::%x:%x', random_int(1, 0xffff), random_int(1, 0xffff)));
        $this->assertResponseIsSuccessful();
    }

    /**
     * @return array<string, mixed>
     */
    private function loginMfaUser(KernelBrowser $client): array
    {
        $this->postLogin($client, self::MFA_EMAIL, self::MFA_PASSWORD);
        $this->assertResponseIsSuccessful();

        return json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function postLogin(KernelBrowser $client, string $email, string $password, ?string $clientIp = null): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($clientIp !== null) {
            $server['REMOTE_ADDR'] = $clientIp;
        }

        $client->request(
            'POST',
            '/api/auth/login',
            server: $server,
            content: json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR)
        );
    }

    private function verifyMfa(KernelBrowser $client, string $mfaToken, string $code): void
    {
        $client->request(
            'POST',
            '/api/auth/mfa/verify',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['token' => $mfaToken, 'code' => $code], JSON_THROW_ON_ERROR)
        );
    }

    /**
     * RFC 6238 TOTP (SHA-1, 30 s step, 6 digits), computed independently of the
     * bundle so the test also checks interoperability with authenticator apps.
     */
    private function totpCode(string $base32Secret, int $timestamp): string
    {
        $counter = pack('J', intdiv($timestamp, 30));
        $hash = hash_hmac('sha1', $counter, $this->base32Decode($base32Secret), true);
        $offset = ord($hash[19]) & 0x0f;
        $value = ((ord($hash[$offset]) & 0x7f) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * A code that is valid for none of the steps accepted by the verifier window.
     */
    private function invalidCode(): string
    {
        $now = time();
        $accepted = [];
        for ($step = -2; $step <= 2; ++$step) {
            $accepted[] = $this->totpCode($this->mfaSecret, $now + $step * 30);
        }

        $candidate = 0;
        while (in_array(sprintf('%06d', $candidate), $accepted, true)) {
            ++$candidate;
        }

        return sprintf('%06d', $candidate);
    }

    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(rtrim(strtoupper($secret), '=')) as $char) {
            $index = strpos($alphabet, $char);
            $this->assertNotFalse($index, 'TOTP secret is not base32');
            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr((int) bindec($byte));
            }
        }

        return $bytes;
    }

    private function resetDatabase(): void
    {
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $tool = new SchemaTool($em);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
        $container->get(AppFixtures::class)->load($em);

        $this->mfaSecret = $container->get(TotpAuthenticatorInterface::class)->generateSecret();
        $mfaUser = (new User())
            ->setEmail(self::MFA_EMAIL)
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true)
            ->setMfaEnabled(true)
            ->setMfaSecret($this->mfaSecret);
        $mfaUser->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($mfaUser, self::MFA_PASSWORD));
        $em->persist($mfaUser);
        $em->flush();
    }
}
