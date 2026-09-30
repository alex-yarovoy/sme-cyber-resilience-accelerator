<?php

namespace App\Controller;

use App\Entity\PublicKeyCredential;
use App\Entity\User;
use App\Service\WebAuthnAttestationVerifier;
use App\Service\WebAuthnChallengeStore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/webauthn', name: 'api_webauthn_')]
class WebAuthnController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WebAuthnChallengeStore $challengeStore,
        private readonly WebAuthnAttestationVerifier $verifier,
    ) {
    }

    #[Route('/options/register', name: 'options_register', methods: ['POST'])]
    public function optionsRegister(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if ($user === null) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $challenge = $this->newChallenge();
        $this->challengeStore->put($challenge, ['type' => 'register', 'email' => $user->getEmail()]);

        return $this->json([
            'publicKey' => [
                'challenge' => $challenge,
                'rp' => [
                    'name' => 'Identity MFA',
                    'id' => 'localhost',
                ],
                'user' => [
                    'id' => $this->base64UrlEncode((string) $user->getId()),
                    'name' => $user->getEmail(),
                    'displayName' => $user->getEmail(),
                ],
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7],
                    ['type' => 'public-key', 'alg' => -257],
                ],
                'timeout' => 300000,
                'attestation' => 'none',
            ],
        ]);
    }

    #[Route('/options/login', name: 'options_login', methods: ['POST'])]
    public function optionsLogin(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if ($user === null) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $challenge = $this->newChallenge();
        $this->challengeStore->put($challenge, ['type' => 'login', 'email' => $user->getEmail()]);

        $credentials = $this->entityManager->getRepository(PublicKeyCredential::class)->findBy(['user' => $user]);
        $allowCredentials = array_map(static fn (PublicKeyCredential $credential) => [
            'type' => 'public-key',
            'id' => $credential->getCredentialId(),
            'transports' => $credential->getTransports() ?? [],
        ], $credentials);

        return $this->json([
            'publicKey' => [
                'challenge' => $challenge,
                'timeout' => 300000,
                'rpId' => 'localhost',
                'allowCredentials' => $allowCredentials,
                'userVerification' => 'preferred',
            ],
        ]);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $credential = json_decode($request->getContent() ?: '[]', true);
        if (!is_array($credential) || !isset($credential['id'], $credential['type']) || $credential['type'] !== 'public-key') {
            return $this->json(['error' => 'Invalid credential payload'], Response::HTTP_BAD_REQUEST);
        }

        $challenge = $this->extractChallenge($credential);
        if ($challenge === null) {
            return $this->json(['error' => 'clientDataJSON challenge missing'], Response::HTTP_BAD_REQUEST);
        }

        $stored = $this->challengeStore->consume($challenge);
        if ($stored === null || ($stored['type'] ?? null) !== 'register') {
            return $this->json(['error' => 'Unknown or expired challenge'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->verifier->verifyRegistration($credential, $challenge)) {
            return $this->json(['error' => 'Registration ceremony rejected'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $email = $stored['email'] ?? null;
        $user = is_string($email) ? $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]) : null;
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $record = (new PublicKeyCredential())
            ->setCredentialId((string) $credential['id'])
            ->setUser($user)
            ->setTransports(isset($credential['transports']) && is_array($credential['transports']) ? $credential['transports'] : null);
        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $this->json(['status' => 'registered', 'credential_id' => $record->getCredentialId()], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $credential = json_decode($request->getContent() ?: '[]', true);
        if (!is_array($credential) || !isset($credential['id'], $credential['type']) || $credential['type'] !== 'public-key') {
            return $this->json(['error' => 'Invalid credential payload'], Response::HTTP_BAD_REQUEST);
        }

        $challenge = $this->extractChallenge($credential);
        if ($challenge === null) {
            return $this->json(['error' => 'clientDataJSON challenge missing'], Response::HTTP_BAD_REQUEST);
        }

        $stored = $this->challengeStore->consume($challenge);
        if ($stored === null || ($stored['type'] ?? null) !== 'login') {
            return $this->json(['error' => 'Unknown or expired challenge'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->verifier->verifyAssertion($credential, $challenge)) {
            return $this->json(['error' => 'Assertion ceremony rejected'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $existing = $this->entityManager->getRepository(PublicKeyCredential::class)->findOneBy([
            'credentialId' => (string) $credential['id'],
        ]);
        if ($existing === null) {
            return $this->json(['error' => 'Unknown credential'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'error' => 'attestation_crypto_follow_on',
            'message' => 'Challenge binding succeeded. Full assertion cryptography is a follow-on release.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function resolveUser(Request $request): ?User
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $email = is_array($data) && isset($data['email']) && is_string($data['email']) ? $data['email'] : null;
        if ($email === null || $email === '') {
            return null;
        }

        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    /**
     * @param array<string, mixed> $credential
     */
    private function extractChallenge(array $credential): ?string
    {
        $response = $credential['response'] ?? null;
        if (!is_array($response) || !isset($response['clientDataJSON']) || !is_string($response['clientDataJSON'])) {
            return null;
        }

        $padded = strtr($response['clientDataJSON'], '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }
        $json = base64_decode($padded, true);
        if ($json === false) {
            return null;
        }
        $clientData = json_decode($json, true);
        if (!is_array($clientData) || !isset($clientData['challenge']) || !is_string($clientData['challenge'])) {
            return null;
        }

        return $clientData['challenge'];
    }

    private function newChallenge(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
