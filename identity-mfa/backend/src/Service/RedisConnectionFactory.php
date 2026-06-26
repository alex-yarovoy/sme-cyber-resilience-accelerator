<?php

namespace App\Service;

class RedisConnectionFactory
{
    public static function createFromUrl(?string $redisUrl): ?\Redis
    {
        if ($redisUrl === null || $redisUrl === '') {
            return null;
        }

        if (!extension_loaded('redis')) {
            return null;
        }

        $parts = parse_url($redisUrl);
        if ($parts === false) {
            return null;
        }

        $host = $parts['host'] ?? '127.0.0.1';
        $port = $parts['port'] ?? 6379;

        $redis = new \Redis();
        if (!$redis->connect($host, (int) $port, 2.0)) {
            return null;
        }

        if (isset($parts['pass'])) {
            $redis->auth($parts['pass']);
        }

        return $redis;
    }
}
