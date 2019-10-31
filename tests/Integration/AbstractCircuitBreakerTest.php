<?php
declare(strict_types=1);

namespace Postcon\Resilience\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Postcon\Resilience\CircuitBreakerInterface;
use Postcon\Resilience\Exceptions\CircuitBreakerTripped;

abstract class AbstractCircuitBreakerTest extends TestCase
{
    protected const NAME       = 'system to be circuit-breaked';
    protected const LIFE_TIME  = 2;
    protected const MAX_ERRORS = 3;

    /** @var CircuitBreakerInterface */
    protected $circuitBreaker;

    /** @test */
    public function it_should_be_available_initially(): void
    {
        self::assertTrue($this->circuitBreaker->isAvailable());
    }

    /** @test */
    public function it_should_stay_available_below_max_errors(): void
    {
        $this->circuitBreaker->reportFailure();
        $this->circuitBreaker->reportFailure();

        self::assertTrue($this->circuitBreaker->isAvailable());
    }

    /** @test */
    public function it_should_become_unavailable_above_max_errors(): void
    {
        self::assertTrue($this->circuitBreaker->isAvailable(), 'pre-condition');

        $this->circuitBreaker->reportFailure();
        $this->circuitBreaker->reportFailure();
        $this->circuitBreaker->reportFailure();
        self::assertFalse($this->circuitBreaker->isAvailable());
    }

    /** @test */
    public function it_should_become_available_after_success(): void
    {
        $this->circuitBreaker->reportFailure();
        $this->circuitBreaker->reportFailure();
        $this->circuitBreaker->reportFailure();
        self::assertFalse($this->circuitBreaker->isAvailable(), 'pre-condition');

        $this->circuitBreaker->reportSuccess();

        self::assertTrue($this->circuitBreaker->isAvailable());
    }

    /** @test */
    public function it_should_become_available_after_timeout(): void
    {
        $this->circuitBreaker->reportFailure();
        $this->circuitBreaker->reportFailure();
        $this->circuitBreaker->reportFailure();
        self::assertFalse($this->circuitBreaker->isAvailable(), 'pre-condition');

        \usleep(self::LIFE_TIME * 1000000 + 100000); // sleep 2100 milliseconds

        self::assertTrue($this->circuitBreaker->isAvailable());
    }

    /** @test */
    public function it_should_stay_unavailable_before_timeout(): void
    {
        $this->circuitBreaker->reportFailure();
        $this->circuitBreaker->reportFailure();
        $this->circuitBreaker->reportFailure();
        self::assertFalse($this->circuitBreaker->isAvailable(), 'pre-condition');

        \usleep(self::LIFE_TIME * 1000000 - 100000); // sleep 1900 milliseconds

        self::assertFalse($this->circuitBreaker->isAvailable());
    }

    /** @test */
    public function it_should_throw_exception_if_unavailable(): void
    {
        $this->expectException(CircuitBreakerTripped::class);
        $this->expectExceptionMessage(sprintf('The service "%s" is currently not available.', self::NAME));

        $this->circuitBreaker->reportFailure();
        $this->circuitBreaker->reportFailure();
        $this->circuitBreaker->reportFailure();

        $this->circuitBreaker->check();
    }
}
