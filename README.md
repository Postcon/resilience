# Resilience library

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

$circuitBreaker = new \Postcon\Resilience\RedisCircuitBreaker($redis, 'system');
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

```

 --------------------------     ::reportSuccess()       ---------------------------
|            CLOSED        |  <--------------------    |            OPEN           |
| ::isAvailable() === true |    exceeding lifetime     | ::isAvailable() === false |
 --------------------------                             ---------------------------
     ^                                                                    ^
     |                                                                    |
     |                                                                    |
     |                    --------------------------    ::reportFailure() |
      ------------------ |         HALF OPEN        | ---------------------
      ::reportSuccess()  | ::isAvailable() === true |
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

## License

All contents of this package are licensed under the [MIT license](LICENSE).