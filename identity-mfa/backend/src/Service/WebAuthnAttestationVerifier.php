<?php

namespace App\Service;

interface WebAuthnAttestationVerifier
{
    /**
     * @param array<string, mixed> $credential
     */
    public function verifyRegistration(array $credential, string $expectedChallenge): bool;

    /**
     * @param array<string, mixed> $credential
     */
    public function verifyAssertion(array $credential, string $expectedChallenge): bool;
}
