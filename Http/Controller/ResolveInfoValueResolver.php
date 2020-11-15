<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Http\Controller;

use GraphQL\Type\Definition\ResolveInfo;
use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Util\RequestUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ResolveInfoValueResolver implements ArgumentValueResolverInterface
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

        if (null !== $argument->getType() && is_a($argument->getType(), ResolveInfo::class, true)) {
            return true;
        }

        if (null === $argument->getType() && 'resolveInfo' === $argument->getName()) {
            return true;
        }

        return false;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $this->context->getCurrentResolveRequest()->getResolveInfo();
    }
}
