<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

class TestAutowireClass
{
    /**
     * @var string|null
     */
    protected $info;

    public function __construct(?string $info = null)
    {
        $this->info = $info;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }
}
