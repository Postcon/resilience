<?php
declare(strict_types=1);

namespace Postcon\Resilience;

class RedisCircuitBreaker extends AbstractCircuitBreaker
{
    /** @var \Redis */
    private $redis;

    /** @var string */
    private $name;

    /** @var int */
    private $lifetime;

    /** @var int */
    private $maxErrors;

    public function __construct(\Redis $redis, string $name, int $lifetime = 120, int $maxErrors = 3)
    {
        $this->redis     = $redis;
        $this->name      = $name;
        $this->lifetime  = $lifetime;
        $this->maxErrors = $maxErrors;
    }

    public function isAvailable(): bool
    {
        return $this->redis->get($this->name) < $this->maxErrors;
    }

    public function reportSuccess(): void
    {
        $this->redis->del($this->name);
    }

    public function reportFailure(): void
    {
        $this->redis->incr($this->name);
        $this->redis->expire($this->name, $this->lifetime);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
