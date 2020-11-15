<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Util;

class TestClass
{
    public $publicInfo;

    protected $info;

    public function __construct(string $info = 'test')
    {
        $this->info = $info;
    }

    public function getInfo(): string
    {
        return $this->info;
    }
}
