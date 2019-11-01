<?php

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Postcon\Resilience\CircuitBreakerInterface;
use Postcon\Resilience\Exceptions\CircuitBreakerTripped;
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

    /**
     * @inheritdoc
     */
    public function sendAsync(RequestInterface $request, array $options = [])
    {
        $this->baseClient->sendAsync($request, $options);
    }

    /**
     * @inheritdoc
     *
     * @throws CircuitBreakerTripped
     */
    public function request($method, $uri, array $options = [])
    {
        return $this->check(function () use ($method, $uri, $options) {
            $this->baseClient->request($method, $uri, $options);
        });
    }

    /**
     * @inheritdoc
     */
    public function requestAsync($method, $uri, array $options = [])
    {
        $this->baseClient->requestAsync($method, $uri, $options);
    }

    /**
     * @inheritdoc
     */
    public function getConfig($option = null)
    {
        return $this->baseClient->getConfig($option);
    }

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
