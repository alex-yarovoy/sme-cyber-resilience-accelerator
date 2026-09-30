<?php

namespace App\Service;

use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimiterService
{
    public function __construct(
        private RateLimiterFactory $loginLimiter,
        private RateLimiterFactory $mfaLimiter,
        private RateLimiterFactory $apiLimiter,
    ) {
    }

    public function isAllowed(string $type, string $key): bool
    {
        return $this->factoryFor($type)->create($key)->consume()->isAccepted();
    }

    public function getRemainingAttempts(string $type, string $key): int
    {
        return $this->factoryFor($type)->create($key)->consume()->getRemainingTokens();
    }

    public function getResetTime(string $type, string $key): ?\DateTimeImmutable
    {
        return $this->factoryFor($type)->create($key)->consume()->getRetryAfter();
    }

    private function factoryFor(string $type): RateLimiterFactory
    {
        return match ($type) {
            'login' => $this->loginLimiter,
            'mfa' => $this->mfaLimiter,
            default => $this->apiLimiter,
        };
    }
}
