import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Orchesty Store

Orchesty Store is one of the freely available Orchesty extensions. It is a collection of ready-made applications and connectors that can be easily modified and extended. Orchesty Store is a library, created using the SDK. In the same way, we can create our own collections and use them across our projects. We'll learn how to build new apps and connectors in our [tutorials](../tutorials/getting-started-with-tutorials.md).

## Available connectors

There is currently a store for PHP and Node.js in Orchesty. We can check available applications and connectors in their repositories:

- [Node.js repository](https://github.com/Orchesty/orchesty-nodejs-connectors)
- [PHP repository](https://github.com/Orchesty/orchesty-php-connectors)

:::tip
The libraries for each language do not contain the same range of connectors and applications. You can use the Store libraries in the project simultaneously.
:::

## Installation

<Tabs>
<TabItem value="nodejs" label="Node.js">

The Orchesty Store is available as a [public package](https://www.npmjs.com/package/@orchesty/nodejs-connectors). Running `pnpm install @orchesty/nodejs-connectors` in the `nodejs-sdk` directory will download the package and store it among our **node_modules**.

:::info
The `nodejs-sdk` directory is valid when using [Orchesty-skeleton](../get-started/installation.md), which uses package manager **pnpm**. The package can be installed into any application you want to integrate with Orchesty.
:::

</TabItem>
<TabItem value="php" label="PHP">

The Orchesty Store is available as a public [package](https://packagist.org/packages/orchesty/php-connectors). Use the [Composer](https://getcomposer.org/) tool to install it. Running the `composer require orchesty/php-connectors` command in the `php-sdk` directory will download the package and save it to our vendor.

:::info
The `php-sdk` directory is valid when using [Orchesty-skeleton](../get-started/installation.md). The package can be installed into any application you want to integrate with Orchesty.
:::


You also need to register a Symfony bundle. Add the following line to the `php-sdk/config/Bundles.php` file:


```php
./config/Bundles.php

Hanaboso\HbPFConnectors\HbPFConnectorsBundle::class => ['all' => TRUE]
```

The last step is to register the applications and connectors as a Symfony service.

```yaml - title="./config/services.yaml"
node_services_dirs:
    - '%kernel.project_dir%/vendor/orchesty/php-connectors/src/Resources/config'

applications:
    - '%kernel.project_dir%/vendor/orchesty/php-connectors/src/Resources/config'
```
</TabItem>
</Tabs>

To add applications to the marketplace in Orchesty Admin, the application must be registered as a service in the orchestration layer. For instructions on how to register, see [SDK settings](../tutorials/SDK-settings.md).

Next, you must add the application and its connectors to the service container. See chapters [Basic application](../tutorials/basic-application.md) and [OAuth2 application](../tutorials/oauth2-application.md). Connectors added in this way are then available for building topologies in the topology editor.
