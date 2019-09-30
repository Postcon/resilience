<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$redis = new \Redis();
$redis->connect('redis', 6379);

$circuitBreaker = new \Postcon\Resilience\RedisCircuitBreaker($redis, 'system');
$circuitBreaker->reportSuccess();

$circuitBreaker->isAvailable(); // should be true
$circuitBreaker->reportFailure();
$circuitBreaker->isAvailable(); // ... still true
$circuitBreaker->reportFailure();
$circuitBreaker->isAvailable(); // ... still true
$circuitBreaker->reportFailure();
$circuitBreaker->isAvailable(); // ... now it is false
