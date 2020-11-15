<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Controller;

use GraphQL\Error\Debug;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Schema;
use Nyholm\Psr7\Factory\Psr17Factory;
use Player259\GraphQLBundle\Service\Context;
use Player259\GraphQLBundle\Service\TypeRegistry;
use Player259\GraphQLBundle\Util\FieldResolverFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GraphQLController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var TypeRegistry
     */
    protected $typeRegistry;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var bool
     */
    protected $debug = false;

    public function __construct(TypeRegistry $typeRegistry, Context $context)
    {
        $this->typeRegistry = $typeRegistry;
        $this->context = $context;
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        $mutationType = $this->typeRegistry->get(TypeRegistry::MUTATION);

        $additionalTypes = array_filter($this->typeRegistry->all(), function (NamedType $type) {
            return !in_array($type->name, [TypeRegistry::QUERY, TypeRegistry::MUTATION]);
        });

        $schema = new Schema([
            'types'    => $additionalTypes,
            'query'    => $this->typeRegistry->get(TypeRegistry::QUERY),
            'mutation' => $mutationType->getFields() ? $mutationType : null,
        ]);

        $schema->assertValid();

        $errorHandler = function (array $errors, callable $formatter) {
            foreach ($errors as $error) {
                if (!$this->logger || !$error instanceof \Throwable) {
                    continue;
                }

                if (!$error instanceof Error) {
                    $this->logger->critical($error->getMessage(), ['exception' => $error]);
                } elseif ($error->getPrevious()) {
                    $level = $error->getPrevious() instanceof Error ? LogLevel::ERROR : LogLevel::CRITICAL;
                    $this->logger->log($level, $error->getMessage(), ['exception' => $error]);
                }
            }

            return array_map($formatter, $errors);
        };

        $this->context->reset();

        $config = ServerConfig::create()
            ->setErrorsHandler($errorHandler)
            ->setSchema($schema)
            ->setQueryBatching(false)
            ->setFieldResolver(FieldResolverFactory::createDefaultFieldResolver())
            ->setContext($this->context);

        // Support for version 14
        if ($this->debug && class_exists(DebugFlag::class) && method_exists($config, 'setDebugFlag')) {
            $config->setDebugFlag(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
        }

        // Versions 0.12.* and 0.13.*
        if ($this->debug && class_exists(Debug::class) && method_exists($config, 'setDebug')) {
            $config->setDebug(Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE);
        }

        $server = new StandardServer($config);

        $contents = $psrRequest->getBody()->getContents();

        $parsedBody = [];
        if (!empty($contents)) {
            $parsedBody = json_decode($contents, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new BadRequestHttpException('Invalid JSON');
            }
        }
        $parsedBody += $psrRequest->getQueryParams();

        $request = $psrRequest->withParsedBody($parsedBody);
        $result = $server->executePsrRequest($request);

        return new JsonResponse($result);
    }
}
