<?php
declare(strict_types=1);

namespace Postcon\Resilience\Tests\Integration;

use Postcon\Resilience\InMemoryCircuitBreaker;

class InMemoryCircuitBreakerTest extends AbstractCircuitBreakerTest
{
    protected function setUp(): void
    {
        $this->circuitBreaker = new InMemoryCircuitBreaker(self::NAME, self::LIFE_TIME, self::MAX_ERRORS);
    }
}
