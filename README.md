# Player259GraphQLBundle

## About

The Player259GraphQLBundle integrates [graphql-php](https://github.com/webonyx/graphql-php) library
into symfony applications.

[![CI Status](https://github.com/player259/graphql-bundle/workflows/CI/badge.svg?branch=master&event=push)](https://github.com/player259/graphql-bundle/actions)

[Usage documentation](https://github.com/player259/graphql-bundle/blob/master/Resources/doc/index.rst).

## Features

* Types-as-services with Dependency Injection
* Controller-like resolvers with Autowiring
* Type definition and resolvers in the same class
* No extra configuration files
* No static calls
* Native graphql-php type system with great [documentation](https://webonyx.github.io/graphql-php/)
* Integrated TypeRegistry
* Possible code splitting for Query and Mutation types
* Simplified Deferred workflow with integrated buffer

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

## Documentation

Usage examples can be found in [documentation](https://github.com/player259/graphql-bundle/blob/master/Resources/doc/index.rst).

## License

Released under the MIT License, see LICENSE.
