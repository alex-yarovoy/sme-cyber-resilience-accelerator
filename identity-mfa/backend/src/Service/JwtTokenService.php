<?php

namespace App\Service;

use App\Entity\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JwtTokenService
{
    public const MFA_TOKEN_TYPE = 'mfa_required';

    private string $jwtSecret;
    private string $jwtAlgorithm;
    private int $refreshTokenExpiry;

    public function __construct(
        ParameterBagInterface $params,
        private readonly TokenDenylistService $tokenDenylist,
    ) {
        $this->jwtSecret = $params->get('jwt_secret');
        $this->jwtAlgorithm = $params->get('jwt_algorithm', 'HS256');
        $this->refreshTokenExpiry = $params->get('jwt_refresh_expiry', 604800);
    }

    public function generateRefreshToken(User $user): string
    {
        $payload = [
            'user_id' => $user->getId(),
            'type' => 'refresh',
            'iat' => time(),
            'exp' => time() + $this->refreshTokenExpiry,
            'jti' => uniqid('refresh_', true),
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
    }

    public function decodeToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));

            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid token: '.$e->getMessage());
        }
    }

    public function decodeRefreshToken(string $token): array
    {
        $payload = $this->decodeToken($token);

        if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
            throw new \InvalidArgumentException('Invalid refresh token type');
        }

        return $payload;
    }

    public function isTokenExpired(array $payload): bool
    {
        return isset($payload['exp']) && $payload['exp'] < time();
    }

    /**
     * Decode a token issued by generateMfaToken(). Rejects tokens of any other
     * type and tokens that were already used for a successful verification.
     *
     * @return array<string, mixed>
     */
    public function decodeMfaToken(string $token): array
    {
        $payload = $this->decodeToken($token);

        if (($payload['type'] ?? null) !== self::MFA_TOKEN_TYPE) {
            throw new \InvalidArgumentException('Invalid MFA token type');
        }

        if ($this->tokenDenylist->isDenied($this->extractTokenId($payload))) {
            throw new \InvalidArgumentException('MFA token already used');
        }

        return $payload;
    }

    /**
     * Mark an MFA token as used for the rest of its lifetime.
     *
     * @param array<string, mixed> $payload payload returned by decodeMfaToken()
     *
     * @return bool false when the token was already used (concurrent replay)
     */
    public function consumeMfaToken(array $payload): bool
    {
        return $this->tokenDenylist->claim($this->extractTokenId($payload), $this->remainingTtl($payload));
    }

    public function generateMfaToken(User $user): string
    {
        $payload = [
            'user_id' => $user->getId(),
            'type' => self::MFA_TOKEN_TYPE,
            'iat' => time(),
            'exp' => time() + 300,
            'jti' => uniqid('mfa_', true),
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
    }

    public function blacklistAccessToken(string $token): void
    {
        $payload = $this->readUnsignedPayload($token);
        if ($payload === null) {
            return;
        }

        $this->tokenDenylist->deny($this->extractTokenId($payload), $this->remainingTtl($payload));
    }

    public function blacklistToken(string $token): void
    {
        $payload = $this->decodeToken($token);
        $tokenId = $this->extractTokenId($payload);
        $ttl = $this->remainingTtl($payload);

        $this->tokenDenylist->deny($tokenId, $ttl);
    }

    public function isTokenBlacklisted(string $token): bool
    {
        try {
            $payload = $this->decodeToken($token);
        } catch (\InvalidArgumentException) {
            return true;
        }

        return $this->tokenDenylist->isDenied($this->extractTokenId($payload));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readUnsignedPayload(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return null;
        }

        $padded = strtr($parts[1], '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }
        $json = base64_decode($padded, true);
        if ($json === false) {
            return null;
        }
        $payload = json_decode($json, true);

        return is_array($payload) ? $payload : null;
    }

    private function extractTokenId(array $payload): string
    {
        if (isset($payload['jti']) && is_string($payload['jti']) && $payload['jti'] !== '') {
            return $payload['jti'];
        }

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function remainingTtl(array $payload): int
    {
        if (!isset($payload['exp']) || !is_numeric($payload['exp'])) {
            return 900;
        }

        return max(1, (int) $payload['exp'] - time());
    }
}
