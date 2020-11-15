<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests;

use Player259\GraphQLBundle\Service\Context;

class FakeContext extends Context
{
    protected $responses = [];

    public function __construct()
    {
    }

    public function addResponseData(string $type, string $method, $data): void
    {
        $this->responses[$type . '::' . $method] = $data;
    }

    public function handle(callable $controller)
    {
        if (!is_array($controller)) {
            throw new \LogicException('Invalid callable to handle, $controller argument should be array, given: ' . gettype($controller));
        }

        $type = is_object($controller[0]) ? get_class($controller[0]) : (string) $controller[0];
        $method = $controller[1];

        if (!isset($this->responses[$type . '::' . $method])) {
            throw new \LogicException('There is no prepared response data in FakeContext for: ' . $type . '::' . $method);
        }

        return $this->responses[$type . '::' . $method];
    }
}
