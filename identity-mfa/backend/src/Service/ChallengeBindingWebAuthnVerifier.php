<?php

namespace App\Service;

class ChallengeBindingWebAuthnVerifier implements WebAuthnAttestationVerifier
{
    public function verifyRegistration(array $credential, string $expectedChallenge): bool
    {
        return $this->challengeMatches($credential, $expectedChallenge)
            && $this->hasRegistrationShape($credential);
    }

    public function verifyAssertion(array $credential, string $expectedChallenge): bool
    {
        return $this->challengeMatches($credential, $expectedChallenge)
            && $this->hasAssertionShape($credential);
    }

    /**
     * @param array<string, mixed> $credential
     */
    private function hasRegistrationShape(array $credential): bool
    {
        $response = $credential['response'] ?? null;
        if (!is_array($response)) {
            return false;
        }

        return isset($credential['id'], $credential['type'], $response['clientDataJSON'], $response['attestationObject']);
    }

    /**
     * @param array<string, mixed> $credential
     */
    private function hasAssertionShape(array $credential): bool
    {
        $response = $credential['response'] ?? null;
        if (!is_array($response)) {
            return false;
        }

        return isset($credential['id'], $credential['type'], $response['clientDataJSON'], $response['authenticatorData'], $response['signature']);
    }

    /**
     * @param array<string, mixed> $credential
     */
    private function challengeMatches(array $credential, string $expectedChallenge): bool
    {
        $response = $credential['response'] ?? null;
        if (!is_array($response) || !isset($response['clientDataJSON']) || !is_string($response['clientDataJSON'])) {
            return false;
        }

        $json = $this->base64UrlDecode($response['clientDataJSON']);
        if ($json === null) {
            return false;
        }

        $clientData = json_decode($json, true);
        if (!is_array($clientData) || !isset($clientData['challenge']) || !is_string($clientData['challenge'])) {
            return false;
        }

        return hash_equals($expectedChallenge, $clientData['challenge']);
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padded = strtr($value, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($padded, true);

        return $decoded === false ? null : $decoded;
    }
}
