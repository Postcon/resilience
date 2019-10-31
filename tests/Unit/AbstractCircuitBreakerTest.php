<?php
declare(strict_types=1);

namespace Postcon\Resilience\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Postcon\Resilience\AbstractCircuitBreaker;
use Postcon\Resilience\CircuitBreakerInterface;
use Postcon\Resilience\Exceptions\CircuitBreakerTripped;

class AbstractCircuitBreakerTest extends TestCase
{
    /** @test */
    public function check_success()
    {
        $circuitBreaker = $this->createCircuitBreaker('system', true);

        self::assertTrue($circuitBreaker->isAvailable(), 'pre_condition');

        $circuitBreaker->check();
    }

    /** @test */
    public function check_failure()
    {
        $this->expectException(CircuitBreakerTripped::class);
        $this->expectExceptionMessage('The service "system" is currently not available.');

        $circuitBreaker = $this->createCircuitBreaker('system', false);

        self::assertFalse($circuitBreaker->isAvailable(), 'pre_condition');

        $circuitBreaker->check();
    }

    private function createCircuitBreaker(string $name, bool $availability): CircuitBreakerInterface
    {
        $circuitBreaker = new class($name, $availability) extends AbstractCircuitBreaker
        {
            private $name;
            private $availability;

            public function __construct(string $name, bool $availability)
            {
                $this->name         = $name;
                $this->availability = $availability;
            }

            public function __toString(): string
            {
                return $this->name;
            }

            public function isAvailable(): bool
            {
                return $this->availability;
            }

            public function reportSuccess(): void
            {
            }

            public function reportFailure(): void
            {
            }
        };

        return $circuitBreaker;
    }
}
