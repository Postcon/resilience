<?php
declare(strict_types=1);

namespace Postcon\Resilience\Tests\Integration;

use Postcon\Resilience\RedisCircuitBreaker;

class RedisCircuitBreakerTest extends AbstractCircuitBreakerTest
{
    protected function setUp(): void
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->flushDB();

        $this->circuitBreaker = new RedisCircuitBreaker($redis, self::NAME, self::LIFE_TIME, self::MAX_ERRORS);
        $this->circuitBreaker->reportSuccess();
    }
}
