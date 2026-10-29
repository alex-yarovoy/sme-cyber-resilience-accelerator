<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TokenDenylistService
{
    private const KEY_PREFIX = 'jwt_denylist:';

    private ?\Redis $redis;

    /** @var array<string, int> */
    private static array $memory = [];

    public function __construct(
        #[Autowire('%env(REDIS_URL)%')] string $redisUrl,
    ) {
        $this->redis = RedisConnectionFactory::createFromUrl($redisUrl);
    }

    public function deny(string $tokenId, int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            return;
        }

        if ($this->redis !== null) {
            $this->redis->setex(self::KEY_PREFIX.$tokenId, $ttlSeconds, '1');
        }

        self::$memory[$tokenId] = time() + $ttlSeconds;
    }

    /**
     * Atomically deny a token id that is not denied yet.
     *
     * @return bool true when this call denied the id, false when it was already denied
     */
    public function claim(string $tokenId, int $ttlSeconds): bool
    {
        $ttlSeconds = max(1, $ttlSeconds);

        if ($this->redis !== null) {
            $claimed = $this->redis->set(self::KEY_PREFIX.$tokenId, '1', ['nx', 'ex' => $ttlSeconds]);
            if ($claimed) {
                self::$memory[$tokenId] = time() + $ttlSeconds;
            }

            return (bool) $claimed;
        }

        if ((self::$memory[$tokenId] ?? 0) > time()) {
            return false;
        }
        self::$memory[$tokenId] = time() + $ttlSeconds;

        return true;
    }

    public function isDenied(string $tokenId): bool
    {
        if ($this->redis !== null && $this->redis->exists(self::KEY_PREFIX.$tokenId)) {
            return true;
        }

        $expiresAt = self::$memory[$tokenId] ?? 0;

        return $expiresAt > time();
    }
}
