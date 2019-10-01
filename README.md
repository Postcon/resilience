# Resilience library

[![Build Status](https://secure.travis-ci.org/DrSchimke/resilience.png)](http://travis-ci.org/DrSchimke/resilience)

A collection of reusable resilience pattern implementations. Currently implemented:

* [circuit breaker](https://martinfowler.com/bliki/CircuitBreaker.html)

## Installation

Using [composer](https://getcomposer.org/download/):

```
composer require postcon/resilience
```

## Simple usage

```php
$redis = new \Redis();

$circuitBreaker = new \Postcon\Resilience\RedisCircuitBreaker($redis, 'system', 120, 3);
$circuitBreaker->reportSuccess();

$circuitBreaker->isAvailable(); // should be true
$circuitBreaker->reportFailure();
$circuitBreaker->isAvailable(); // ... still true
$circuitBreaker->reportFailure();
$circuitBreaker->isAvailable(); // ... still true
$circuitBreaker->reportFailure();
$circuitBreaker->isAvailable(); // ... now it is false

$circuitBreaker->check(); // throws CircuitBreakerTripped exception, if 'system' is not available.
```

## State transitions

The circuit breaker can be on one of three states: CLOSED (system is available), HALF OPEN (system is still available)
and OPEN (system is not available).

The _normal_ state of the circuit breaker is CLOSED; i.e. the system is working correctly. If a failure is reported,
the state changes to HALF OPEN. If either a success is reported, or a defined time exceeds (_lifetime_), the state
becomes CLOSED again. If failure is reported repeatedly (_maxErrors_), the state changes from HALF OPEN to OPEN
(_the circuit breaker is tripped_).

The OPEN state changes back to CLOSED, after exceeding a defined time. Depending on the usage of this circuit breaker
implementation, a reported success could change the OPEN state to be CLOSED as well.

```
 --------------------------      ::reportSuccess()        ---------------------------
|          CLOSED          | <-------------------------- |            OPEN           |
| ::isAvailable() === true |    exceeding lifetime       | ::isAvailable() === false |
 --------------------------                               ---------------------------
       ^      |                                                                 ^
       |      |   ::reportFailure()                                             |
       |       -------------------------                                        |
       |                                |                                       |
       |                                v                                       |
       |  ::reportSuccess()   --------------------------                        |
        -------------------- |         HALF OPEN        | ----------------------
         exceeding lifetime  | ::isAvailable() === true |  rpt. ::reportFailure()
                              --------------------------
```


## Examples

This circuit breaker implementation can be used to [decorate](examples/CircuitBreakerClientDecorator.php) e.g. [guzzle http client](https://github.com/guzzle/guzzle/): 

```php
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Postcon\Resilience\CircuitBreakerInterface;
use Postcon\Resilience\CircuitBreakerTripped;
use Psr\Http\Message\RequestInterface;

class CircuitBreakerClientDecorator implements ClientInterface
{
    /** @var ClientInterface */
    private $baseClient;

    /** @var CircuitBreakerInterface */
    private $circuitBreaker;

    public function __construct(ClientInterface $baseClient, CircuitBreakerInterface $circuitBreaker)
    {
        $this->baseClient     = $baseClient;
        $this->circuitBreaker = $circuitBreaker;
    }

    /**
     * @inheritdoc
     *
     * @throws CircuitBreakerTripped
     */
    public function send(RequestInterface $request, array $options = [])
    {
        return $this->check(function () use ($request, $options) {
            $this->baseClient->send($request, $options);
        });
    }

    // ...

    /**
     * @throws GuzzleException
     * @throws CircuitBreakerTripped
     */
    private function check(callable $function)
    {
        $this->circuitBreaker->check();

        try {
            $result = $function();

            $this->circuitBreaker->reportSuccess();

            return $result;
        } catch (ConnectException $e) {
            $this->circuitBreaker->reportFailure();
            throw $e;
        } catch (ServerException $e) {
            $this->circuitBreaker->reportFailure();
            throw $e;
        } catch (ClientException $e) {
            $this->circuitBreaker->reportSuccess();
            throw $e;
        }
    }
}
```

## Implementation details

Currently, there is a [redis](https://redis.io/) [implementation](lib/RedisCircuitBreaker.php) of the circuit
breaker pattern. An instance of a circuit breaker is persisted as the respective failure counter, where the redis key
is the circuit breaker name.

To implement the lifetime feature (automatic state transition to CLOSED after some time), the redis [EXPIRE command](https://redis.io/commands/expire) is used.

## License

All contents of this package are licensed under the [MIT license](LICENSE).
