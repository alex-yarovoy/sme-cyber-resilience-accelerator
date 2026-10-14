<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    private function createPreparedClient(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();
        $this->resetDatabase();

        return $client;
    }

    public function testLoginRejectsInvalidCredentials(): void
    {
        $client = $this->createPreparedClient();
        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'admin@example.com', 'password' => 'wrong-password'], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginSucceedsForValidUserWithoutMfa(): void
    {
        $payload = $this->loginAdmin($this->createPreparedClient());
        $this->assertArrayHasKey('access_token', $payload);
        $this->assertNotEmpty($payload['access_token']);
        $this->assertArrayHasKey('refresh_token', $payload);
        $this->assertNotEmpty($payload['refresh_token']);
    }

    public function testFailedLoginIncrementsMetrics(): void
    {
        $client = $this->createPreparedClient();
        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'admin@example.com', 'password' => 'wrong-password'], JSON_THROW_ON_ERROR)
        );

        $client->request('GET', '/api/metrics');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('app_auth_login_failed_total 1', $client->getResponse()->getContent());
    }

    public function testRefreshRotatesToken(): void
    {
        $client = $this->createPreparedClient();
        $login = $this->loginAdmin($client);
        $originalRefresh = $login['refresh_token'];

        $client->request(
            'POST',
            '/api/auth/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['refresh_token' => $originalRefresh], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();
        $rotated = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($rotated['access_token']);
        $this->assertNotSame($originalRefresh, $rotated['refresh_token']);

        $client->request(
            'POST',
            '/api/auth/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['refresh_token' => $originalRefresh], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLogoutDenylistsAccessToken(): void
    {
        $client = $this->createPreparedClient();
        $login = $this->loginAdmin($client);

        $client->request(
            'POST',
            '/api/auth/logout',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.$login['access_token'],
            ],
            content: json_encode(['refresh_token' => $login['refresh_token']], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseIsSuccessful();

        $client->request(
            'GET',
            '/api/auth/me',
            server: ['HTTP_AUTHORIZATION' => 'Bearer '.$login['access_token']]
        );
        $this->assertResponseStatusCodeSame(401);
    }

    public function testWebAuthnOptionsRegisterReturnsPublicKey(): void
    {
        $client = $this->createPreparedClient();
        $client->request(
            'POST',
            '/api/webauthn/options/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'admin@example.com'], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('publicKey', $payload);
        $this->assertNotEmpty($payload['publicKey']['challenge']);
        $this->assertSame('Identity MFA', $payload['publicKey']['rp']['name']);
    }

    /**
     * @return array<string, mixed>
     */
    private function loginAdmin(KernelBrowser $client): array
    {
        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'admin@example.com', 'password' => 'Admin#123456'], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();

        return json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
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
    }
}
