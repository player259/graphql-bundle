<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests;

use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

class FakeResolveInfo extends ResolveInfo
{
    protected $responses = [];

    public function __construct(NamedType $parentType, string $fieldName)
    {
        $this->parentType = $parentType;
        $this->fieldName = $fieldName;
        $this->path = [];
    }

    public static function create(string $parentTypeName, string $fieldName): self
    {
        $parentType = new ObjectType(['name' => $parentTypeName]);

        return new self($parentType, $fieldName);
    }
}
