<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Service;

use GraphQL\Type\Definition\ResolveInfo;

class ResolveRequest
{
    /**
     * @var mixed
     */
    protected $root;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var ResolveInfo
     */
    protected $resolveInfo;

    public function __construct($root, array $args, ResolveInfo $info)
    {
        $this->root = $root;
        $this->args = $args;
        $this->resolveInfo = $info;
    }

    /**
     * @return mixed
     */
    public function getRoot()
    {
        return $this->root;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function getResolveInfo(): ResolveInfo
    {
        return $this->resolveInfo;
    }
}
