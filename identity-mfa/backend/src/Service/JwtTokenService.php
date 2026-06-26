<?php

namespace App\Service;

use App\Entity\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JwtTokenService
{
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

    public function generateMfaToken(User $user): string
    {
        $payload = [
            'user_id' => $user->getId(),
            'type' => 'mfa_required',
            'iat' => time(),
            'exp' => time() + 300,
            'jti' => uniqid('mfa_', true),
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
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
