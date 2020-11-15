Player259GraphQLBundle
======================

About
-----

The Player259GraphQLBundle integrates `graphql-php <https://github.com/webonyx/graphql-php>`_ library
into symfony applications.

Installation
------------

The Player259GraphQLBundle requires PHP 7.1+ and Symfony 4.4+.

You can install the bundle using `Symfony Flex <https://symfony.com/doc/current/setup/flex.html>`_:

.. code-block :: bash

    $ composer require player259/graphql-bundle

.. note::

    If you're not using Flex, then add the bundle to your ``config/bundles.php``:

    .. code-block :: php

        // config/bundles.php
        return [
            // ...
            Player259\GraphQLBundle\Player259GraphQLBundle::class => ['all' => true],
        ];

    Import routing file:

    .. code-block :: yaml

        # in app/config/routes.yaml
        player259_graphql:
            resource: '@Player259GraphQLBundle/Resources/config/routing.xml'
            prefix: /

    Or assign endpoint to specific url:

    .. code-block :: yaml

        # in app/config/routes.yaml
        player259_graphql_index:
            path: /graphql
            controller: Player259\GraphQLBundle\Controller\GraphQLController

By default bundle registers ``/graphql`` endpoint.

Configuration
-------------

Default configuration in ``config/packages/player259_graphql.yaml``.

.. code-block :: yaml

    # in app/config/packages/player259_graphql.yml
    player259_graphql:
        debug: '%kernel.debug%'
        logger: '?logger'

With ``debug`` option set to ``true`` response errors will contain ``debugMessage`` and ``trace``.

``logger`` parameter is a service name to log exceptions.
If it's prefixed with ``?`` it will not throw exception if no such service exists.

Registering types
-----------------

Create a new type class:

.. code-block :: php

    <?php

    namespace App\GraphQL;

    use GraphQL\Type\Definition\ObjectType;
    use Player259\GraphQLBundle\Service\TypeRegistry;

    class QueryType extends ObjectType
    {
        public function __construct(TypeRegistry $typeRegistry)
        {
            $config = [
                'name' => 'Query',
                'fields' => static function() use ($typeRegistry) {
                    return [
                        'users' => [
                            'type' => $typeRegistry->get(UserType::class),
                        ],
                    ];
                },
            ];

            parent::__construct($config);
        }
    }

If you are using recent version of Symfony it'll take all the configuration stuff.
It will register Type as service and assign ``player259_graphql.type``.
After that, all services tagged with ``player259_graphql.type`` will be added to TypeRegistry automatically.

If autoconfiguration disabled try this:

.. code-block :: yaml

    # app/config/services.yml
    services:
        _instanceof:
            GraphQL\Type\Definition\NamedType:
                tags: ['player259_graphql.type']

Type services use Framework Controller argument resolvers.
This is achieved by tagging them with ``controller.service_arguments``.
So if you have problems with autowiring resolvers, try adding this tag manually.

Lazy loading
------------

It's highly recommended to use lazy loading for Type fields:

.. code-block :: php

    <?php

    namespace App\GraphQL;

    use GraphQL\Type\Definition\ObjectType;
    use Player259\GraphQLBundle\Service\TypeRegistry;

    class QueryType extends ObjectType
    {
        public function __construct(TypeRegistry $typeRegistry)
        {
            $config = [
                'name' => 'Query',
                // Here it is, anonymous function, static is not required
                'fields' => static function() use ($typeRegistry) {
                    return [
                        'users' => [
                            'type' => $typeRegistry->get(UserType::class),
                        ],
                    ];
                },
            ];

            // With PHP 7.4 and arrow function
            $config = [
                'name' => 'Query',
                'fields' => fn() => [
                    'users' => [
                        'type' => $typeRegistry->get(UserType::class),
                    ],
                ],
            ];

            parent::__construct($config);
        }
    }

More information `here <http://webonyx.github.io/graphql-php/type-system/schema/#lazy-loading-of-types>`_.

Adding resolvers
-----------------

Basic usage
###########

Bundle has own implementation of defaultFieldResolver.
It uses public methods in type class as resolvers.

If ``App\GraphQL\SomeType`` has configured GraphQL field ``someField``,
there can be resolver method named the same as field or prefixed with ``resolve``:

.. code-block :: php

    public function someField() {
        // ...
    }
    public function resolveSomeField() {
        // ...
    }

Prefixed names are suitable for queries, e.g. ``resolveUsers``.

Non-prefixed are better for mutations, e.g. ``createUser``.

``resolve`` prefix is optional and can be omitted.

Nested Type resolving
#####################

Another use case, nested Types in one class.

.. code-block :: php

    <?php

    namespace App\GraphQL;

    use GraphQL\Type\Definition\ObjectType;
    use GraphQL\Type\Definition\Type;
    use Player259\GraphQLBundle\Util\FieldResolverFactory;

    class QueryType extends ObjectType
    {
        public function __construct()
        {
            $config = [
                'name' => 'Query',
                'fields' => [
                    'someField' => new ObjectType([
                        'name' => 'SomeType',
                        'fields' => [
                            'someField' => Type::string(),
                        ],
                        // This will allow resolver search for methods in $this class
                        'resolveField' => FieldResolverFactory::createDefaultFieldResolver($this),
                    ]),
                ],
            ];

            parent::__construct($config);
        }

        public function someField() {
            // ...
        }

        public function someTypeSomeField() {
            // ...
        }
    }

There are two ``someField`` fields but in different Types.
Default resolver will try to find method with parentType name.

For the nested field available method names are:

* ``someTypeSomeField``
* ``resolveSomeTypeSomeField``

For root field it's:

* ``querySomeField``
* ``resolveQuerySomeField``

If there will be only one method ``someField``, it will resolve both fields.

DefaultFieldResolver doesn't know which type is ancestor of field's parentType.
So it's necessary to pass ``$this`` explicitly.

.. code-block :: php

    [
        // ...
        'resolveField' => FieldResolverFactory::createDefaultFieldResolver($this),
    ]

List of all available methods
#############################

Type ``Type`` with field ``field`` could be resolved with these methods in priority order:

* ``resolveTypeFieldDeferred``
* ``someTypeFieldDeferred``
* ``resolveTypeField``
* ``typeField``
* ``resolveFieldDeferred``
* ``fieldDeferred``
* ``resolveField``
* ``field``

Other resolve options
#####################

It there is no method to call, but ``$root`` is presented, defaultFieldResolver will try
to extract value from it.
For an array or ``ArrayAccess`` by key.
For an object by public property or getter using ``symfony/property-access``.

Only fields with different name should be overrided with own resolver method.
So entity fields with the same name will be resolved automatically.

Autowiring
----------

Types with tag ``player259_graphql.type`` (which forces tag ``controller.service_arguments``)
are act as controllers. So each public method can be called with autowired arguments.

.. code-block :: php

    // Basic arguments
    public function field($root, array $args, \GraphQL\Type\Definition\ResolveInfo $resolveInfo) {
        // ...
    }

    // Additional service
    public function field(EntityManagerInterface $em, $root, array $args) {
        // ...
    }

    // Autowired root, if it has User class, it will be autowired too
    // You don't have to name it $root all the time
    public function field(User $user, array $args) {
        // ...
    }

Deffered resolving
------------------

Types support native graphql-php Deferred using,
as described in `documentation <https://webonyx.github.io/graphql-php/data-fetching/#solving-n1-problem>`_.

Bundle provides another way to use them with helper service.

1. First you should add ``Deferred`` suffix to resolve method.
2. Then inject ``Player259\GraphQLBundle\Service\DeferredResolver`` as dependency.
3. Use ``$deferredResolver->resolve(callable $callback)`` to resolve buffered requests.

Example:

.. code-block :: php

    <?php

    namespace App\GraphQL;

    use App\Entity\User;
    use App\Repository\UserRepository;
    use GraphQL\Type\Definition\ObjectType;
    use GraphQL\Type\Definition\Type;
    use Player259\GraphQLBundle\Service\ResolveRequestCollection;
    use Player259\GraphQLBundle\Service\DeferredResolver;
    use Player259\GraphQLBundle\Service\TypeRegistry;

    class QueryType extends ObjectType
    {
        public function __construct(TypeRegistry $typeRegistry)
        {
            $config = [
                'name' => 'Query',
                'fields' => function () use ($typeRegistry) {
                    return [
                        'user' => [
                            'type' => $typeRegistry->get(User::class),
                            'args' => [
                                'id' => Type::int(),
                            ],
                        ],
                    ];
                },
            ];

            parent::__construct($config);
        }

        public function resolveUserDeferred(array $args, DeferredResolver $deferredResolver, UserRepository $userRepository): ?User
        {
            // Collection class is used to provide autocomplete without extra @var phpdoc
            // It contains array of Player259\GraphQLBundle\Service\ResolveRequest objects
            $resolvedData = $deferredResolver->resolve(function (ResolveRequestCollection $requests) {
                $ids = [];
                foreach ($requests as $request) {
                    $ids[] = $request->getArgs()['id'];
                }

                return $userRepository->findByIdsIndexedById($ids);
            });

            return $resolvedData[$args['id']] ?? null;
        }
    }

Code splitting
--------------

GraphQL schema doesn't allow duplicate type names.
But Query and Mutation could be splitted into multiple files.

It's may be useful to hold all the feature code in separate directory.
Or split one huge Type into multiple chunks to improve readability.

So there may be multiple classes, but finally they will be merged into one GraphQL Type.

Feature Query:

.. code-block :: php

    <?php

    // Feature Query Type, which contains only feature fields
    namespace App\Feature\GraphQL;

    use GraphQL\Type\Definition\ObjectType;
    use GraphQL\Type\Definition\Type;

    class QueryType extends ObjectType
    {
        public function __construct()
        {
            $config = [
                'name' => 'Query',
                'fields' => [
                    'featureField' => Type::string(),
                ],
            ];

            parent::__construct($config);
        }
    }

Common Query:

.. code-block :: php

    <?php

    // Root Query with common fields
    namespace App\GraphQL;

    use GraphQL\Type\Definition\ObjectType;
    use GraphQL\Type\Definition\Type;

    class QueryType extends ObjectType
    {
        public function __construct()
        {
            $config = [
                'name' => 'Query',
                'fields' => [
                    'field' => Type::string(),
                ],
            ];

            parent::__construct($config);
        }
    }

So final Query type will contain both ``featureField`` and ``field``

There is a possibily of field name collision, plase take this into account.

Also, during merging any extra options will be lost.
Currently, only ``name``, ``description`` and ``fields`` are transferred into new type.

Not yet implemented
-------------------

Pass execution rules, disabling introspection, query depth and complexity.

Dispatching events to override server parameters such as promiseAdapter, error formatters and handlers.

Allow to merge non-root types to get more flexibility.

Maybe custom type config property ``resolveMethod`` to call specific method or another service.

Another option is annotations, something like ``@GraphQL\Resolve("App\GraphQL\QueryType", "users")``
so it could be attached to any service with public method.
There will be no autowiring but it can be useful in some cases.

License
-------

Released under the MIT License, see LICENSE.
