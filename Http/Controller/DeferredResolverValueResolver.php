<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Http\Controller;

use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Service\DeferredResolver;
use Player259\GraphQLBundle\Util\RequestUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class DeferredResolverValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if (!RequestUtil::isGraphQLSubRequest($request)) {
            return false;
        }

        if (null === $this->context->getCurrentResolveRequest()) {
            return false;
        }

        if (null !== $argument->getType() && is_a($argument->getType(), DeferredResolver::class, true)) {
            return true;
        }

        if (null === $argument->getType() && 'deferredResolver' === $argument->getName()) {
            return true;
        }

        return false;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $this->context->getCurrentDeferredResolver(false);
    }
}
