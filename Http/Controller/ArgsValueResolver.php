<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Http\Controller;

use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Util\RequestUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ArgsValueResolver implements ArgumentValueResolverInterface
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

        return (null === $argument->getType() || 'array' === $argument->getType()) && 'args' === $argument->getName();
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $this->context->getCurrentResolveRequest()->getArgs();
    }
}
