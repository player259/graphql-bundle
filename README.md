# Player259GraphQLBundle

## About

The Player259GraphQLBundle integrates [webonyx/graphql-php](https://github.com/webonyx/graphql-php) library
into symfony applications.

[![CI Status](https://github.com/player259/graphql-bundle/workflows/CI/badge.svg?branch=master&event=push)](https://github.com/player259/graphql-bundle/actions)

[Usage documentation](https://github.com/player259/graphql-bundle/blob/master/Resources/doc/index.rst).

## Features

* Types-as-services with Dependency Injection
* Controller-like resolvers with [Autowiring](https://github.com/player259/graphql-bundle/blob/master/Resources/doc/index.rst#autowiring)
* Type definition and resolvers in the same class, see [Basic usage](https://github.com/player259/graphql-bundle/blob/master/Resources/doc/index.rst#basic-usage)
* No extra configuration files
* No static calls
* Native [webonyx/graphql-php](https://github.com/webonyx/graphql-php) type system with all its features and great [documentation](https://webonyx.github.io/graphql-php/)
* Integrated [Type Registry](https://github.com/player259/graphql-bundle/blob/master/Resources/doc/index.rst#type-registry)
* Possible [Code splitting](https://github.com/player259/graphql-bundle/blob/master/Resources/doc/index.rst#code-splitting) for Query and Mutation types
* Simplified [Deferred resolving](https://github.com/player259/graphql-bundle/blob/master/Resources/doc/index.rst#deferred-resolving) with integrated buffer

## Installation

The Player259GraphQLBundle requires PHP 7.1+ and Symfony 4.4+.

You can install the bundle using [Symfony Flex](https://symfony.com/doc/current/setup/flex.html):

    $ composer require player259/graphql-bundle

If you're not using Flex, then add the bundle to your `config/bundles.php`:

```php
// config/bundles.php
return [
    // ...
    Player259\GraphQLBundle\Player259GraphQLBundle::class => ['all' => true],
];
```

Import routing file:

```yaml
# in app/config/routes.yaml
player259_graphql:
    resource: '@Player259GraphQLBundle/Resources/config/routing.xml'
    prefix: /
```

Or assign endpoint to specific url:

```yaml
# in app/config/routes.yaml
player259_graphql_index:
    path: /graphql
    controller: Player259\GraphQLBundle\Controller\GraphQLController
```

By default bundle registers `/graphql` endpoint.

## Configuration

Default configuration in `config/packages/player259_graphql.yaml`.

```yaml
# in app/config/packages/player259_graphql.yml
player259_graphql:
    debug: '%kernel.debug%'
    logger: '?logger'
```

With `debug` option set to `true` response errors will contain `debugMessage` and `trace`.

`logger` parameter is a service name to log exceptions.  
If it's prefixed with `?` it will not throw exception if no such service exists.

## Example

To create your first GraphQL API (with default Symfony 5 installation and no configuration):

1. Create class which extends webonyx `ObjectType` with name `Query`
2. Add at least one field and resolver
3. Make a request to `/graphql` url

```php
<?php

namespace App\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Security\Core\Security;

class QueryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Query',
            'fields' => [
                'username' => [
                    'type' => Type::string(),
                    'description' => 'Current User username',
                ],
            ],
        ];

        parent::__construct($config);
    }

    public function resolveUsername(Security $security): ?string
    {
        return $security->getUser() ? $security->getUser()->getUsername() : null;
    }
}
```

## Documentation

Usage examples can be found in [documentation](https://github.com/player259/graphql-bundle/blob/master/Resources/doc/index.rst).


## Not yet implemented

Pass execution rules, disabling introspection, query depth and complexity.

Dispatching events to override server parameters such as promiseAdapter, error formatters and handlers.

Allow to merge non-root types to get more flexibility.

Maybe custom type config property `resolveMethod` to call specific method or another service.

Another option is annotations, something like `@GraphQL\Resolve("App\GraphQL\QueryType", "users")`
so it could be attached to any service with public method.
There will be no autowiring but it can be useful in some cases.

## License

Released under the MIT License, see LICENSE.
