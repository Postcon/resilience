<?php
declare(strict_types=1);

namespace Postcon\Resilience;

use Postcon\Resilience\Exceptions\CircuitBreakerTripped;

abstract class AbstractCircuitBreaker implements CircuitBreakerInterface
{
    final public function check(): void
    {
        if (!$this->isAvailable()) {
            throw new CircuitBreakerTripped(sprintf('The service "%s" is currently not available.', (string)$this));
        }
    }

    abstract public function __toString(): string;
}
