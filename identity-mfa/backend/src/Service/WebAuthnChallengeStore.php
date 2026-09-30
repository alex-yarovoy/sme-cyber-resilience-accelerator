<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class WebAuthnChallengeStore
{
    private const KEY_PREFIX = 'webauthn_challenge:';
    private const TTL = 300;

    private ?\Redis $redis;

    /** @var array<string, array{expires: int, payload: array<string, mixed>}> */
    private static array $memory = [];

    public function __construct(
        #[Autowire('%env(REDIS_URL)%')] string $redisUrl,
    ) {
        $this->redis = RedisConnectionFactory::createFromUrl($redisUrl);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function put(string $challenge, array $payload): void
    {
        $record = ['expires' => time() + self::TTL, 'payload' => $payload];
        $encoded = json_encode($record, JSON_THROW_ON_ERROR);

        if ($this->redis !== null) {
            $this->redis->setex(self::KEY_PREFIX.$challenge, self::TTL, $encoded);
        }

        self::$memory[$challenge] = $record;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function consume(string $challenge): ?array
    {
        if ($this->redis !== null) {
            $raw = $this->redis->get(self::KEY_PREFIX.$challenge);
            if (!is_string($raw) || $raw === '') {
                return null;
            }
            $this->redis->del(self::KEY_PREFIX.$challenge);
            $decoded = json_decode($raw, true);
            if (!is_array($decoded) || !isset($decoded['payload']) || !is_array($decoded['payload'])) {
                return null;
            }

            return $decoded['payload'];
        }

        $record = self::$memory[$challenge] ?? null;
        unset(self::$memory[$challenge]);
        if ($record === null || $record['expires'] < time()) {
            return null;
        }

        return $record['payload'];
    }
}
