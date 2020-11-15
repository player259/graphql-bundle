<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Contracts\Service\ResetInterface;

class TestLogger extends AbstractLogger implements ResetInterface
{
    protected $messages = [];

    public function log($level, $message, array $context = [])
    {
        $this->messages[] = [
            'level'   => $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function reset(): void
    {
        $this->messages = [];
    }

    public function getMessages(?array $levelFilter = null): array
    {
        return array_values(array_filter($this->messages, function (array $message) use ($levelFilter) {
            return null !== $levelFilter ? in_array($message['level'] ?? null, $levelFilter) : true;
        }));
    }

    public function getErrorMessages(): array
    {
        return $this->getMessages([LogLevel::ERROR, LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY]);
    }

    public function getErrors(): string
    {
        return implode(' ', array_map(function (array $message) {
            return $message['message'];
        }, $this->getErrorMessages()));
    }
}
