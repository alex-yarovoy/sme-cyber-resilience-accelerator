<?php

namespace App\Service;

use App\Entity\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JwtTokenService
{
    private string $jwtSecret;
    private string $jwtAlgorithm;
    private int $refreshTokenExpiry;

    public function __construct(ParameterBagInterface $params)
    {
        $this->jwtSecret = $params->get('jwt_secret');
        $this->jwtAlgorithm = $params->get('jwt_algorithm', 'HS256');
        $this->refreshTokenExpiry = $params->get('jwt_refresh_expiry', 604800); // 7 days
    }

    public function generateRefreshToken(User $user): string
    {
        $payload = [
            'user_id' => $user->getId(),
            'type' => 'refresh',
            'iat' => time(),
            'exp' => time() + $this->refreshTokenExpiry,
            'jti' => uniqid('refresh_', true)
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
    }

    public function decodeToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid token: ' . $e->getMessage());
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
            'exp' => time() + 300, // 5 minutes
            'jti' => uniqid('mfa_', true)
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
    }

    public function blacklistToken(string $token): void
    {
        // In a real implementation, you would store this in Redis
        // For now, we'll just validate the token structure
        try {
            $this->decodeToken($token);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid token to blacklist');
        }
    }

    public function isTokenBlacklisted(string $token): bool
    {
        // In a real implementation, you would check Redis
        // For now, return false (not blacklisted)
        return false;
    }
}
