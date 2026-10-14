<?php

namespace App\Tests\Service;

use App\Service\ChallengeBindingWebAuthnVerifier;
use PHPUnit\Framework\TestCase;

class ChallengeBindingWebAuthnVerifierTest extends TestCase
{
    public function testRegistrationAcceptsMatchingChallenge(): void
    {
        $challenge = 'abc123';
        $clientData = $this->encodeClientData(['type' => 'webauthn.create', 'challenge' => $challenge]);
        $verifier = new ChallengeBindingWebAuthnVerifier();

        $this->assertTrue($verifier->verifyRegistration([
            'id' => 'cred-1',
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $clientData,
                'attestationObject' => 'AAAA',
            ],
        ], $challenge));
    }

    public function testRegistrationRejectsChallengeMismatch(): void
    {
        $clientData = $this->encodeClientData(['type' => 'webauthn.create', 'challenge' => 'other']);
        $verifier = new ChallengeBindingWebAuthnVerifier();

        $this->assertFalse($verifier->verifyRegistration([
            'id' => 'cred-1',
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $clientData,
                'attestationObject' => 'AAAA',
            ],
        ], 'abc123'));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encodeClientData(array $data): string
    {
        return rtrim(strtr(base64_encode(json_encode($data, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }
}
