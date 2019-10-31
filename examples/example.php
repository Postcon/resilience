<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$redis = new \Redis();
$redis->connect('redis', 6379);

$circuitBreaker = new \Postcon\Resilience\RedisCircuitBreaker($redis, 'system', 120, 3);

// testing maxErrors
$circuitBreaker->reportSuccess();
$circuitBreaker->isAvailable(); // should be true
$circuitBreaker->reportFailure();
$circuitBreaker->isAvailable(); // ... still true
$circuitBreaker->reportFailure();
$circuitBreaker->isAvailable(); // ... still true
$circuitBreaker->reportFailure();
$circuitBreaker->isAvailable(); // ... now it is false

// testing lifetime
$circuitBreaker->reportSuccess();
$circuitBreaker->reportFailure();
$circuitBreaker->reportFailure();
$circuitBreaker->reportFailure();
$circuitBreaker->isAvailable(); // should be false
sleep(120 + 1);
$circuitBreaker->isAvailable(); // ... true again
