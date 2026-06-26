<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TokenDenylistService
{
    private const KEY_PREFIX = 'jwt_denylist:';

    private ?\Redis $redis;

    public function __construct(
        #[Autowire('%env(REDIS_URL)%')] string $redisUrl,
    ) {
        $this->redis = RedisConnectionFactory::createFromUrl($redisUrl);
    }

    public function deny(string $tokenId, int $ttlSeconds): void
    {
        if ($this->redis === null || $ttlSeconds <= 0) {
            return;
        }

        $this->redis->setex(self::KEY_PREFIX.$tokenId, $ttlSeconds, '1');
    }

    public function isDenied(string $tokenId): bool
    {
        if ($this->redis === null) {
            return false;
        }

        return (bool) $this->redis->exists(self::KEY_PREFIX.$tokenId);
    }
}
