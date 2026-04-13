<?php

namespace App\Service;

use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

class RateLimiterService
{
    private array $rateLimiters = [];

    public function __construct(
        private StorageInterface $storage
    ) {}

    public function isAllowed(string $type, string $key): bool
    {
        $rateLimiter = $this->getRateLimiter($type);
        $limit = $rateLimiter->consume($key);
        
        return $limit->isAccepted();
    }

    public function getRemainingAttempts(string $type, string $key): int
    {
        $rateLimiter = $this->getRateLimiter($type);
        $limit = $rateLimiter->consume($key);
        
        return $limit->getRemainingTokens();
    }

    public function getResetTime(string $type, string $key): ?\DateTimeImmutable
    {
        $rateLimiter = $this->getRateLimiter($type);
        $limit = $rateLimiter->consume($key);
        
        return $limit->getRetryAfter();
    }

    private function getRateLimiter(string $type): RateLimiterFactory
    {
        if (!isset($this->rateLimiters[$type])) {
            $this->rateLimiters[$type] = $this->createRateLimiter($type);
        }

        return $this->rateLimiters[$type];
    }

    private function createRateLimiter(string $type): RateLimiterFactory
    {
        $config = $this->getRateLimitConfig($type);
        
        return new RateLimiterFactory(
            $config,
            $this->storage
        );
    }

    private function getRateLimitConfig(string $type): array
    {
        return match ($type) {
            'login' => [
                'policy' => 'token_bucket',
                'limit' => 5,
                'interval' => '1 minute',
            ],
            'mfa' => [
                'policy' => 'token_bucket',
                'limit' => 3,
                'interval' => '1 minute',
            ],
            'password_reset' => [
                'policy' => 'token_bucket',
                'limit' => 3,
                'interval' => '1 hour',
            ],
            'api' => [
                'policy' => 'token_bucket',
                'limit' => 100,
                'interval' => '1 minute',
            ],
            'registration' => [
                'policy' => 'token_bucket',
                'limit' => 3,
                'interval' => '1 hour',
            ],
            default => [
                'policy' => 'token_bucket',
                'limit' => 10,
                'interval' => '1 minute',
            ],
        };
    }
}
