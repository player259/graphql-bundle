<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Util;

use GraphQL\Deferred;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Service\ResolveRequest;
use Player259\GraphQLBundle\Service\TypeRegistry;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class FieldResolverFactory
{
    public static function createDefaultFieldResolver(?NamedType $additionalType = null): callable
    {
        return function ($root, $args, Context $context, ResolveInfo $info) use ($additionalType) {
            $currentResolveRequest = new ResolveRequest($root, $args, $info);
            $context->setCurrentResolveRequest($currentResolveRequest);

            $supposedTypes = [];

            $mergedTypes = $info->parentType->config[TypeRegistry::MERGED_TYPES_PROPERTY] ?? [];
            foreach ($mergedTypes as $mergedType) {
                $supposedTypes[] = $mergedType;
            }
            $supposedTypes[] = $info->parentType;
            if (null !== $additionalType) {
                $supposedTypes[] = $additionalType;
            }
            $supposedTypes = array_filter($supposedTypes, function (NamedType $type) {
                return get_class($type) !== ObjectType::class;
            });
            $supposedTypes = array_reverse($supposedTypes);

            $resolveCallback = null;
            $isDeferred = false;

            foreach (self::getSupposedMethods($info) as $supposedMethod) {
                foreach ($supposedTypes as $type) {
                    if (self::hasMethod($type, $supposedMethod)) {
                        $resolveCallback = [$type, $supposedMethod];
                        $isDeferred = substr_compare($supposedMethod, 'Deferred', -strlen('Deferred')) === 0;
                        break 2;
                    }
                }
            }

            $result = null;
            $resolved = false;

            if (!$resolved && null !== $resolveCallback && !$isDeferred) {
                $result = $context->handle($resolveCallback);
                $resolved = true;
            }

            if (!$resolved && null !== $resolveCallback && $isDeferred) {
                $context->getCurrentDeferredResolver(true)->defer($currentResolveRequest);

                $result = new Deferred(function () use ($context, $resolveCallback, $currentResolveRequest) {
                    $context->setCurrentResolveRequest($currentResolveRequest);
                    $result = $context->handle($resolveCallback);
                    $context->setCurrentResolveRequest(null);

                    return $result;
                });

                $resolved = true;
            }

            if (!$resolved && (is_array($root) || $root instanceof \ArrayAccess) && array_key_exists($info->fieldName, $root)) {
                $result = $root[$info->fieldName];
                $resolved = true;
            }

            $propertyAccessor = new PropertyAccessor();
            if (!$resolved && null !== $root && $propertyAccessor->isReadable($root, $info->fieldName)) {
                $result = $propertyAccessor->getValue($root, $info->fieldName);
                $resolved = true;
            }

            if (!$resolved) {
                throw new \LogicException(sprintf(
                    'Can\'t resolve field `%s` of type `%s` (%s)',
                    $info->fieldName,
                    $info->parentType->name,
                    get_class($info->parentType)
                ));
            }

            $context->setCurrentResolveRequest(null);

            return $result;
        };
    }

    protected static function hasMethod(NamedType $type, string $method): bool
    {
        $parentClass = get_parent_class($type);
        $parentDoesntHaveMethod = false !== $parentClass && !method_exists($parentClass, $method);

        return $parentDoesntHaveMethod && is_callable([$type, $method]);
    }

    protected static function getSupposedMethods(ResolveInfo $info): array
    {
        $result = [];

        $result[] = 'resolve' . ucfirst($info->parentType->name) . ucfirst($info->fieldName) . 'Deferred';
        $result[] = lcfirst($info->parentType->name) . ucfirst($info->fieldName) . 'Deferred';
        $result[] = 'resolve' . ucfirst($info->parentType->name) . ucfirst($info->fieldName);
        $result[] = lcfirst($info->parentType->name) . ucfirst($info->fieldName);

        $result[] = 'resolve' . ucfirst($info->fieldName) . 'Deferred';
        $result[] = lcfirst($info->fieldName) . 'Deferred';
        $result[] = 'resolve' . ucfirst($info->fieldName);
        $result[] = lcfirst($info->fieldName);

        return $result;
    }
}
