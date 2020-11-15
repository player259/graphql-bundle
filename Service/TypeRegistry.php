<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Service;

use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;

class TypeRegistry
{
    /**
     * @var array
     */
    protected $types;

    /**
     * @var array
     */
    protected $merged;

    const MERGED_TYPES_PROPERTY = 'mergedTypes';

    const QUERY = 'Query';
    const MUTATION = 'Mutation';

    const MERGED_TYPES = [self::QUERY, self::MUTATION];

    public function __construct()
    {
        $this->types = [];
        $this->merged = [];
    }

    public function add(NamedType $type): self
    {
        if (!property_exists($type, 'name')) {
            throw new \LogicException('Object doesn\'t have property name');
        }
        $typeName = $type->name;

        $typeClass = get_class($type) !== ObjectType::class ? get_class($type) : null;
        $isMerged = in_array($typeName, self::MERGED_TYPES);

        if ($isMerged && null === $typeClass) {
            throw new \LogicException('Can\'t add mergable type to registry, please extend ObjectType: ' . $typeName);
        }

        if (null !== $typeClass) {
            if (isset($this->types[$typeClass])) {
                throw new \LogicException('Type already in registry: ' . $typeClass);
            }
            $this->types[$typeClass] = $type;
        }

        if (!$isMerged) {
            if (isset($this->types[$typeName])) {
                throw new \LogicException('Type already in registry: ' . $typeName);
            }
            $this->types[$typeName] = $type;
        }

        return $this;
    }

    /**
     * @param string $typeClassOrName
     *
     * @return NamedType|ScalarType|ObjectType|Type|mixed
     */
    public function get(string $typeClassOrName)
    {
        $isMerged = in_array($typeClassOrName, self::MERGED_TYPES);
        if ($isMerged) {
            return $this->buildMergedType($typeClassOrName);
        }

        if (!isset($this->types[$typeClassOrName])) {
            throw new \LogicException('Type not found: ' . $typeClassOrName);
        }

        return $this->types[$typeClassOrName];
    }

    protected function buildMergedType(string $typeName): ObjectType
    {
        if (isset($this->merged[$typeName])) {
            return $this->merged[$typeName];
        }

        $fields = [];
        $types = [];
        $description = null;
        $fieldResolver = null;

        foreach ($this->types as $type) {
            if ($typeName !== $type->name) {
                continue;
            }
            if (!$type instanceof ObjectType) {
                throw new \LogicException('Please extend ObjectType in order to support merging: ' . $type);
            }
            if ($duplicateFields = array_intersect_key($fields, $type->getFields())) { // phpcs:ignore
                $list = implode(', ', array_keys($duplicateFields));
                throw new \LogicException('Found duplicate field names during merging: ' . $typeName . ' with fields ' . $list);
            }
            $fields = array_merge($fields, $type->getFields());

            $description = $description ?? $type->description;
            $fieldResolver = $fieldResolver ?? $type->resolveFieldFn;

            $types[] = $type;
        }

        $result = new ObjectType([
            'name'                      => $typeName,
            'description'               => $description,
            'fields'                    => function () use ($fields) {
                return $fields;
            },
            self::MERGED_TYPES_PROPERTY => $types,
        ]);

        $this->merged[$typeName] = $result;

        return $result;
    }

    /**
     * @return NamedType[]
     */
    public function all(): array
    {
        $result = [];
        foreach ($this->types as $type) {
            $result[$type->name] = $result[$type->name] ?? $this->get($type->name);
        }

        return array_values($result);
    }
}
