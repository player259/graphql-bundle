<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Contracts\Service\ResetInterface;

class Context implements ResetInterface
{
    /**
     * @var ArgumentResolverInterface
     */
    protected $argumentResolver;

    /**
     * @var ResolveRequest|null
     */
    protected $resolveRequest;

    /**
     * @var DeferredResolver[]
     */
    protected $deferredResolvers = [];

    public function __construct(ArgumentResolverInterface $argumentResolver)
    {
        $this->argumentResolver = $argumentResolver;
    }

    public function reset(): void
    {
        $this->resolveRequest = null;
        $this->deferredResolvers = [];
    }

    public function setCurrentResolveRequest(?ResolveRequest $resolveRequest): void
    {
        $this->resolveRequest = $resolveRequest;
    }

    public function getCurrentResolveRequest(): ?ResolveRequest
    {
        return $this->resolveRequest;
    }

    public function getCurrentDeferredResolver(bool $createIfInvalid = false): DeferredResolver
    {
        if (!$this->resolveRequest) {
            throw new \LogicException('There is no current ResolveRequest in Context');
        }

        $info = $this->resolveRequest->getResolveInfo();

        $key = $info->parentType->name . '->' . $info->fieldName;

        if (!$createIfInvalid && !isset($this->deferredResolvers[$key])) {
            throw new \LogicException('DeferredResolver was not initialized, may be you try to use it outside of *Deferred resolve method: ' . $key);
        }

        if ($createIfInvalid && (!isset($this->deferredResolvers[$key]) || $this->deferredResolvers[$key]->isResolved())) {
            $this->deferredResolvers[$key] = new DeferredResolver($this->resolveRequest->getResolveInfo());
        }

        return $this->deferredResolvers[$key];
    }

    public function handle(callable $controller)
    {
        $request = new Request();
        $request->attributes->set('_controller', $controller);

        if (is_array($controller) && isset($controller[0]) && isset($controller[1]) && is_object($controller[0])) {
            $request->attributes->set('_controller', [get_class($controller[0]), $controller[1]]);
        }

        $args = $this->argumentResolver->getArguments($request, $controller);

        return call_user_func_array($controller, $args);
    }
}
