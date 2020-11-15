<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Tests\Controller;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Response;

class GraphQLControllerTest extends TestCase
{
    /**
     * @var TestControllerKernel|null
     */
    protected $kernel;

    public function tearDown(): void
    {
        if ($this->kernel) {
            exec('rm -rf ' . escapeshellarg($this->kernel->getCacheDir()));
        }
    }

    protected function createBrowser(array $configuration = [], array $services = []): KernelBrowser
    {
        $kernel = new TestControllerKernel($configuration);
        $kernel
            ->setService('logger', (new Definition(TestLogger::class))->setPublic(true))
            ->setService(TestQueryType::class, (new Definition(TestQueryType::class))->setAutowired(true)->addTag('player259_graphql.type')->addTag('controller.service_arguments'));

        foreach ($services as $id => $service) {
            $kernel->setService($id, $service);
        }

        // Kernel is kept only for deleting file cache
        $this->kernel = $kernel;

        return new KernelBrowser($kernel);
    }

    protected function doGraphQLRequest(KernelBrowser $browser, string $query, array $variables = []): array
    {
        $browser->request(
            'POST',
            '/api/graphql',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            \json_encode(['query' => $query, 'variables' => $variables], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $response = $browser->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccessful(), $this->getErrorLogs($browser));

        $data = \json_decode($response->getContent(), true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Invalid response JSON: ' . \json_last_error_msg() . PHP_EOL . $response->getContent());

        return $data;
    }

    protected function getErrorLogs(KernelBrowser $browser): string
    {
        return $browser->getContainer()->get('logger')->getErrors();
    }

    protected function assertGraphQLResponseSuccessful(array $data): void
    {
        $message = implode('; ', array_map(function (array $error) {
            return $error['debugMessage'] ?? $error['message'] ?? '';
        }, $data['errors'] ?? []));

        static::assertArrayNotHasKey('errors', $data, $message);
    }

    protected function cleanupGraphQLErrorData(array $data): array
    {
        if (isset($data['errors']) && is_array($data['errors'])) {
            foreach ($data['errors'] as &$error) {
                unset($error['debugMessage']);
                unset($error['trace']);
                unset($error['locations']);
                unset($error['path']);
                unset($error);
            }
        }

        return $data;
    }

    protected function convertLegacyErrorFormat(array $data): array
    {
        // Workaround to detect 0.12 version, maybe there is better way
        $isLegacy = (new \ReflectionMethod(ResolveInfo::class, '__construct'))->getNumberOfParameters() === 1;

        if ($isLegacy && isset($data['errors']) && is_array($data['errors'])) {
            foreach ($data['errors'] as &$error) {
                $result = [];
                foreach ($error as $key => $value) {
                    $result[$key] = $value;
                    if ($key === 'message' && array_key_exists('category', $error)) {
                        $result['extensions'] = [
                            'category' => $error['category'],
                        ];
                    }
                }
                unset($result['category']);
                unset($result['code']);
                $error = $result;
                unset($error);
            }
        }

        return $data;
    }

    public function testQuery()
    {
        $browser = $this->createBrowser();

        $actual = $this->doGraphQLRequest($browser, 'query { stub }');
        $this->assertGraphQLResponseSuccessful($actual);

        $this->assertEquals(['data' => ['stub' => 'hello']], $actual);
    }

    public function testQueryAnotherEndpoint()
    {
        $browser = $this->createBrowser();

        $browser->request(
            'POST',
            '/graphql-endpoint',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            \json_encode(['query' => 'query { stub }'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $response = $browser->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccessful(), $this->getErrorLogs($browser));

        $actual = \json_decode($response->getContent(), true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Invalid response JSON: ' . \json_last_error_msg() . PHP_EOL . $response->getContent());

        $this->assertEquals(['data' => ['stub' => 'hello']], $actual);
    }

    public function testGetQuery()
    {
        $browser = $this->createBrowser();

        $browser->request(
            'GET',
            '/api/graphql',
            ['query' => 'query { stub }']
        );
        $this->assertTrue($browser->getResponse()->isSuccessful(), $this->getErrorLogs($browser));

        $actual = json_decode($browser->getResponse()->getContent(), true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Invalid response JSON: ' . \json_last_error_msg());

        $this->assertGraphQLResponseSuccessful($actual);

        $this->assertEquals(['data' => ['stub' => 'hello']], $actual);
    }

    public function testMutation()
    {
        $browser = $this->createBrowser([], [TestMutationType::class => TestMutationType::class]);

        $actual = $this->doGraphQLRequest($browser, 'mutation { do(value: "TEST") }');
        $this->assertGraphQLResponseSuccessful($actual);

        $this->assertEquals(['data' => ['do' => 'TEST']], $actual);
    }

    /**
     * @dataProvider provideExceptionDebugData
     */
    public function testException(bool $debug)
    {
        $browser = $this->createBrowser(['player259_graphql' => ['debug' => $debug]]);

        $actual = $this->doGraphQLRequest($browser, 'query { exception }');
        $actual = $this->convertLegacyErrorFormat($actual);

        $expected = [
            'data'   => [
                'exception' => null,
            ],
            'errors' => [
                [
                    'message'    => 'Internal server error',
                    'extensions' => [
                        'category' => 'internal',
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $this->cleanupGraphQLErrorData($actual));

        $messages = $browser->getContainer()->get('logger')->getErrorMessages();
        $this->assertCount(1, $messages);

        $this->assertEquals('critical', $messages[0]['level']);
        $this->assertEquals('Test exception', $messages[0]['message']);

        /** @var Error $exception */
        $exception = $messages[0]['context']['exception'] ?? null;

        $this->assertInstanceOf(Error::class, $exception);
        $this->assertEquals('Test exception', $exception->getMessage());
        $this->assertNotNull($exception->getPrevious());
        $this->assertInstanceOf(TestException::class, $exception->getPrevious());
        $this->assertEquals('Test exception', $exception->getPrevious()->getMessage());
    }

    /**
     * @dataProvider provideExceptionDebugData
     */
    public function testErrorException(bool $debug)
    {
        $browser = $this->createBrowser(['player259_graphql' => ['debug' => $debug]]);

        $actual = $this->doGraphQLRequest($browser, 'query { errorException }');
        $actual = $this->convertLegacyErrorFormat($actual);

        $expected = [
            'data'   => [
                'errorException' => null,
            ],
            'errors' => [
                [
                    'message'    => 'Internal server error',
                    'extensions' => [
                        'category' => 'test',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->cleanupGraphQLErrorData($actual));

        $messages = $browser->getContainer()->get('logger')->getErrorMessages();
        $this->assertCount(1, $messages);

        $this->assertEquals('error', $messages[0]['level']);
        $this->assertEquals('Test error exception', $messages[0]['message']);

        /** @var Error $exception */
        $exception = $messages[0]['context']['exception'] ?? null;

        $this->assertInstanceOf(Error::class, $exception);
        $this->assertEquals('Test error exception', $exception->getMessage());
        $this->assertNotNull($exception->getPrevious());
        $this->assertInstanceOf(TestErrorException::class, $exception->getPrevious());
        $this->assertEquals('Test error exception', $exception->getPrevious()->getMessage());
    }

    public function provideExceptionDebugData(): \Generator
    {
        yield [true];

        yield [false];
    }

    /**
     * @dataProvider provideDebugData
     */
    public function testDebug(bool $debug, array $expectedErrorFields)
    {
        $browser = $this->createBrowser(['player259_graphql' => ['debug' => $debug]]);

        $actual = $this->doGraphQLRequest($browser, 'query { exception }');
        $actual = $this->convertLegacyErrorFormat($actual);

        $this->assertArrayHasKey('errors', $actual);
        $this->assertCount(1, $actual['errors']);
        $this->assertEquals($expectedErrorFields, array_keys($actual['errors'][0]));
    }

    public function provideDebugData(): \Generator
    {
        yield [
            true,
            [
                'debugMessage',
                'message',
                'extensions',
                'locations',
                'path',
                'trace',
            ],
        ];

        yield [
            false,
            [
                'message',
                'extensions',
                'locations',
                'path',
            ],
        ];
    }

    /**
     * @dataProvider provideNoLoggerData
     */
    public function testNoLogger(?string $logger)
    {
        $browser = $this->createBrowser(['player259_graphql' => ['logger' => $logger]]);

        $actual = $this->doGraphQLRequest($browser, 'query { exception }');
        $actual = $this->convertLegacyErrorFormat($actual);

        $expected = [
            'data'   => [
                'exception' => null,
            ],
            'errors' => [
                [
                    'message'    => 'Internal server error',
                    'extensions' => [
                        'category' => 'internal',
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $this->cleanupGraphQLErrorData($actual));
    }

    public function provideNoLoggerData(): \Generator
    {
        yield [
            null,
        ];

        yield [
            '?non_existing_service',
        ];
    }

    public function testInvalidLogger()
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('The service "Player259\GraphQLBundle\Controller\GraphQLController" has a dependency on a non-existent service "non_existing_service"');

        $browser = $this->createBrowser(['player259_graphql' => ['logger' => 'non_existing_service']]);

        $this->doGraphQLRequest($browser, 'query { exception }');
    }

    public function testInvalidJsonRequest()
    {
        $browser = $this->createBrowser();

        $browser->request(
            'POST',
            '/api/graphql',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{Invalid JSON'
        );

        $this->assertEquals(400, $browser->getResponse()->getStatusCode());
        $this->assertStringContainsString('Invalid JSON (400 Bad Request)', $browser->getResponse()->getContent());
    }

    public function testTaggingNamedTypes()
    {
        $queryTypeDefinition = (new Definition(AnotherTestQueryType::class))
            ->setAutowired(true)
            // When set to true, bundle will automatically add *type* tag to graphql NamedType services
            ->setAutoconfigured(true);

        $browser = $this->createBrowser([], [
            AnotherTestQueryType::class => $queryTypeDefinition,
        ]);

        $actual = $this->doGraphQLRequest($browser, 'query { anotherStub }');
        $this->assertGraphQLResponseSuccessful($actual);

        $this->assertEquals(['data' => ['anotherStub' => 'hi']], $actual);
    }

    /**
     * @dataProvider provideResolveMethodsData
     */
    public function testResolveMethods(string $query, array $expected)
    {
        $browser = $this->createBrowser([], [ResolveTestQueryType::class => ResolveTestQueryType::class]);

        $actual = $this->doGraphQLRequest($browser, $query);
        $this->assertGraphQLResponseSuccessful($actual);

        $this->assertEquals($expected, $actual);
    }

    public function provideResolveMethodsData(): \Generator
    {
        yield [
            'query { field }',
            ['data' => ['field' => 'field']],
        ];

        yield [
            'query { resolvableField }',
            ['data' => ['resolvableField' => 'resolveResolvableField']],
        ];

        yield [
            'query { deferrableField }',
            ['data' => ['deferrableField' => 'deferrableFieldDeferred']],
        ];

        yield [
            'query { deferrableResolvableField }',
            ['data' => ['deferrableResolvableField' => 'resolveDeferrableResolvableFieldDeferred']],
        ];

        yield [
            'query { rootField { bypassedField } }',
            ['data' => ['rootField' => ['bypassedField' => 'rootField']]],
        ];

        yield [
            'query { rootField { handledField } }',
            ['data' => ['rootField' => ['handledField' => 'rootHandledField']]],
        ];

        yield [
            'query { alias: rootField { alias: handledField } }',
            ['data' => ['alias' => ['alias' => 'rootHandledField']]],
        ];

        yield [
            'query { rootField { deferrableField } }',
            ['data' => ['rootField' => ['deferrableField' => 'rootDeferrableFieldDeferred']]],
        ];

        yield [
            'query { resolvableRootField { bypassedField } }',
            ['data' => ['resolvableRootField' => ['bypassedField' => 'resolveResolvableRootField']]],
        ];

        yield [
            'query { resolvableRootField { handledField } }',
            ['data' => ['resolvableRootField' => ['handledField' => 'resolveResolvableRootHandledField']]],
        ];

        yield [
            'query { alias: resolvableRootField { alias: handledField } }',
            ['data' => ['alias' => ['alias' => 'resolveResolvableRootHandledField']]],
        ];

        yield [
            'query { resolvableRootField { deferrableField } }',
            ['data' => ['resolvableRootField' => ['deferrableField' => 'resolveResolvableRootDeferrableFieldDeferred']]],
        ];
    }

    public function testResolveFieldDeferred()
    {
        $browser = $this->createBrowser([], [DeferredTestQueryType::class => DeferredTestQueryType::class]);

        $actual = $this->doGraphQLRequest($browser, 'query { a: field(id: 1) b: field(id: 3) c: field(id: 5) }');
        $this->assertGraphQLResponseSuccessful($actual);

        $expected = [
            'data' => [
                'a' => 'fieldDeferred_1',
                'b' => 'fieldDeferred_3',
                'c' => 'fieldDeferred_5',
            ],
        ];
        $this->assertEquals($expected, $actual);

        $actual = $browser->getContainer()->get(DeferredTestQueryType::class)->getCalls();
        $this->assertEquals(['fieldDeferred__1_3_5'], $actual);
    }

    public function testResolveListDeferred()
    {
        $browser = $this->createBrowser([], [DeferredTestQueryType::class => DeferredTestQueryType::class]);

        $actual = $this->doGraphQLRequest($browser, 'query { list { field } }');
        $this->assertGraphQLResponseSuccessful($actual);

        $expected = [
            'data' => [
                'list' => [
                    ['field' => 'listItemFieldDeferred_1'],
                    ['field' => 'listItemFieldDeferred_3'],
                    ['field' => 'listItemFieldDeferred_5'],
                ],
            ],
        ];
        $this->assertEquals($expected, $actual);

        $actual = $browser->getContainer()->get(DeferredTestQueryType::class)->getCalls();
        $this->assertEquals(['listItemFieldDeferred__1_3_5'], $actual);
    }

    /**
     * Maybe this test doesn't necessary, as it covered with documentation
     * https://webonyx.github.io/graphql-php/data-fetching/#solving-n1-problem
     * Even though *field* is located on different levels of the query - it can be buffered in the same buffer
     */
    public function testNestedDeferredData()
    {
        $browser = $this->createBrowser([], [DeferredTestQueryType::class => DeferredTestQueryType::class]);

        $query = <<<GQL
query {
    deferrable {
        a: field(id: 1)
        b: field(id: 3)
        nested {
            a: field(id: 1)
            b: field(id: 3)
        }
    }
}
GQL;

        $actual = $this->doGraphQLRequest($browser, $query);
        $this->assertGraphQLResponseSuccessful($actual);

        $expected = [
            'data' => [
                'deferrable' => [
                    'a'      => 'fieldDeferred_1',
                    'b'      => 'fieldDeferred_3',
                    'nested' => [
                        'a' => 'fieldDeferred_1',
                        'b' => 'fieldDeferred_3',
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $actual);

        $actual = $browser->getContainer()->get(DeferredTestQueryType::class)->getCalls();
        $this->assertEquals(['fieldDeferred__1_3_1_3'], $actual);
    }

    public function testMergeQueryTypes()
    {
        $browser = $this->createBrowser([], [AnotherTestQueryType::class => AnotherTestQueryType::class]);

        $actual = $this->doGraphQLRequest($browser, 'query { stub anotherStub }');
        $this->assertGraphQLResponseSuccessful($actual);

        $this->assertEquals(['data' => ['stub' => 'hello', 'anotherStub' => 'hi']], $actual);
    }

    public function testDuplicateType()
    {
        $browser = $this->createBrowser([], [TestType::class => TestType::class, TestDuplicateType::class => TestDuplicateType::class]);

        $browser->disableReboot();
        $browser->getKernel()->boot();

        $browser->request(
            'POST',
            '/api/graphql',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            \json_encode(['query' => 'query { stub }'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $response = $browser->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Type already in registry: Test (500 Internal Server Error)', $browser->getResponse()->getContent());
    }

    /**
     * @dataProvider provideAutowireData
     */
    public function testAutowire(string $query, array $expected)
    {
        $browser = $this->createBrowser([], [
            AutowireTestQueryType::class => AutowireTestQueryType::class,
            TestAutowireClass::class     => new TestAutowireClass('QWERTY'),
        ]);
        $browser->getKernel()->boot();

        $actual = $this->doGraphQLRequest($browser, $query);
        $this->assertGraphQLResponseSuccessful($actual);

        $this->assertEquals($expected, $actual);
    }

    public function provideAutowireData(): \Generator
    {
        yield [
            'query { root { nested } }',
            ['data' => ['root' => ['nested' => TestAutowireClass::class]]],
        ];

        yield [
            'query { rootNoHint { nested } }',
            ['data' => ['rootNoHint' => ['nested' => TestAutowireClass::class]]],
        ];

        yield [
            'query { args (id: 111 name: "qwerty") }',
            ['data' => ['args' => ['id', 'name', '111', 'qwerty']]],
        ];

        yield [
            'query { argsNoHint (id: 111 name: "qwerty") }',
            ['data' => ['argsNoHint' => ['id', 'name', '111', 'qwerty']]],
        ];

        yield [
            'query { resolveInfo }',
            ['data' => ['resolveInfo' => 'Query/resolveInfo']],
        ];

        yield [
            'query { resolveInfoNoHint }',
            ['data' => ['resolveInfoNoHint' => 'Query/resolveInfoNoHint']],
        ];

        yield [
            'query { deferredResolver }',
            ['data' => ['deferredResolver' => 'Player259\GraphQLBundle\Service\DeferredResolver']],
        ];

        yield [
            'query { deferredResolverNoHint }',
            ['data' => ['deferredResolverNoHint' => 'Player259\GraphQLBundle\Service\DeferredResolver']],
        ];

        yield [
            'query { all (id: 111 name: "qwerty") }',
            [
                'data' => [
                    'all' => [
                        null,
                        '111',
                        'qwerty',
                        'Query/all',
                        'QWERTY',
                    ],
                ],
            ],
        ];

        yield [
            'query { allNoHint (id: 111 name: "qwerty") }',
            [
                'data' => [
                    'allNoHint' => [
                        null,
                        '111',
                        'qwerty',
                        'Query/allNoHint',
                        'QWERTY',
                    ],
                ],
            ],
        ];
    }

    public function testAutowireDeferredResolverError()
    {
        $browser = $this->createBrowser([], [AutowireTestQueryType::class => AutowireTestQueryType::class]);
        $browser->getKernel()->boot();

        $actual = $this->doGraphQLRequest($browser, 'query { deferredResolverError }');
        $actual = $this->convertLegacyErrorFormat($actual);

        $this->assertEquals('DeferredResolver was not initialized, may be you try to use it outside of *Deferred resolve method: Query->deferredResolverError', $actual['errors'][0]['debugMessage'] ?? null);

        $expected = [
            'data'   => [
                'deferredResolverError' => null,
            ],
            'errors' => [
                [
                    'message'    => 'Internal server error',
                    'extensions' => [
                        'category' => 'internal',
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $this->cleanupGraphQLErrorData($actual));
    }
}
