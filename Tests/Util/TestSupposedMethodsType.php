<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Util;

use GraphQL\Type\Definition\ObjectType;

class TestSupposedMethodsType extends ObjectType
{
    public function __construct()
    {
        parent::__construct(['name' => 'Parent']);
    }

    public function resolveParentADeferred()
    {
    }

    public function parentADeferred()
    {
    }

    public function resolveParentA()
    {
    }

    public function parentA()
    {
    }

    public function resolveADeferred()
    {
    }

    public function aDeferred()
    {
    }

    public function resolveA()
    {
    }

    public function a()
    {
    }

    public function parentBDeferred()
    {
    }

    public function resolveParentB()
    {
    }

    public function parentB()
    {
    }

    public function resolveBDeferred()
    {
    }

    public function bDeferred()
    {
    }

    public function resolveB()
    {
    }

    public function b()
    {
    }

    public function resolveParentC()
    {
    }

    public function parentC()
    {
    }

    public function resolveCDeferred()
    {
    }

    public function cDeferred()
    {
    }

    public function resolveC()
    {
    }

    public function c()
    {
    }

    public function parentD()
    {
    }

    public function resolveDDeferred()
    {
    }

    public function dDeferred()
    {
    }

    public function resolveD()
    {
    }

    public function d()
    {
    }

    public function resolveEDeferred()
    {
    }

    public function eDeferred()
    {
    }

    public function resolveE()
    {
    }

    public function e()
    {
    }

    public function fDeferred()
    {
    }

    public function resolveF()
    {
    }

    public function f()
    {
    }

    public function resolveG()
    {
    }

    public function g()
    {
    }

    public function h()
    {
    }
}
