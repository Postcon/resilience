<?php
declare(strict_types=1);

namespace Postcon\Resilience;

class RedisCircuitBreaker extends AbstractCircuitBreaker
{
    /** @var \Redis */
    private $redis;

    /** @var string */
    private $name;

    /** @var string */
    private $key;

    /** @var int */
    private $lifetime;

    /** @var int */
    private $maxErrors;

    public function __construct(\Redis $redis, string $name, int $lifetime = 120, int $maxErrors = 3)
    {
        $this->redis     = $redis;
        $this->name      = $name;
        $this->key       = sprintf('[%s]:%s', __CLASS__, $name);
        $this->lifetime  = $lifetime;
        $this->maxErrors = $maxErrors;
    }

    public function isAvailable(): bool
    {
        return $this->redis->get($this->key) < $this->maxErrors;
    }

    public function reportSuccess(): void
    {
        $this->redis->del($this->key);
    }

    public function reportFailure(): void
    {
        $this->redis->incr($this->key);
        $this->redis->expire($this->key, $this->lifetime);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
