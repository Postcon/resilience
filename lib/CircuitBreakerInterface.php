<?php
declare(strict_types=1);

namespace Postcon\Resilience;

use Postcon\Resilience\Exceptions\CircuitBreakerTripped;

interface CircuitBreakerInterface
{
    /**
     * @throws CircuitBreakerTripped
     */
    public function check(): void;
    public function isAvailable(): bool;
    public function reportSuccess(): void;
    public function reportFailure(): void;
}
