<?php
declare(strict_types=1);

namespace Postcon\Resilience;

class InMemoryCircuitBreaker extends AbstractCircuitBreaker
{
    /** @var string */
    private $name;

    /** @var int */
    private $lifetime;

    /** @var int */
    private $maxErrors;

    /** @var int */
    private $failures = 0;

    /** @var float */
    private $lastErrorTs = 0;

    public function __construct(string $name, int $lifetime = 120, int $maxErrors = 3)
    {
        $this->name      = $name;
        $this->lifetime  = $lifetime;
        $this->maxErrors = $maxErrors;
    }

    public function isAvailable(): bool
    {
        return $this->failures < $this->maxErrors || $this->lifetimeExceeded();
    }

    public function reportSuccess(): void
    {
        $this->failures = 0;
    }

    public function reportFailure(): void
    {
        if ($this->lifetimeExceeded()) {
            $this->failures = 0;
        }

        ++$this->failures;

        $this->lastErrorTs = \microtime(true);
    }

    public function __toString(): string
    {
        return $this->name;
    }

    private function lifetimeExceeded(): bool
    {
        return \microtime(true) - $this->lastErrorTs > $this->lifetime;
    }
}
