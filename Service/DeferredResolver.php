<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Service;

use GraphQL\Type\Definition\ResolveInfo;

class DeferredResolver
{
    /**
     * @var ResolveInfo
     */
    protected $info;

    /**
     * @var ResolveRequestCollection
     */
    protected $requests;

    /**
     * @var mixed
     */
    protected $resolvedData;

    /**
     * @var bool
     */
    protected $isResolved;

    public function __construct(ResolveInfo $info)
    {
        $this->info = $info;
        $this->requests = new ResolveRequestCollection();
        $this->resolvedData = null;
        $this->isResolved = false;
    }

    public function defer(ResolveRequest $resolveRequest): void
    {
        $currentField = $this->info->parentType->name . '->' . $this->info->fieldName;
        $inputField = $resolveRequest->getResolveInfo()->parentType->name . '->' . $resolveRequest->getResolveInfo()->fieldName;

        if ($currentField !== $inputField) {
            throw new \LogicException('Can\'t defer request for another field: ' . $inputField . ', expected: ' . $currentField);
        }

        if ($this->isResolved) {
            throw new \LogicException('Can\'t defer request if DeferredResolver already resolved: ' . $currentField);
        }

        $this->requests->add($resolveRequest);
    }

    /**
     * @param callable $callback
     *
     * @return mixed
     */
    public function resolve(callable $callback)
    {
        if (!$this->isResolved) {
            $this->resolvedData = call_user_func($callback, $this->requests);
            $this->isResolved = true;
        }

        return $this->resolvedData;
    }

    public function isResolved(): bool
    {
        return $this->isResolved;
    }
}
