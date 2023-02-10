import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Orchesty SDK

Orchesty SDK packages belong to the integration layer. They have 2 key functions:

- They provide a framework for creating connectors and applications.
- They provide communication between the integrated service and the orchestration layer.

:::note Useful links
- [We recommend reading our tutorials](../tutorials/getting-started-with-tutorials.md)
:::

## SDK in Orchesty-skeleton

The SDK can be used within the Orchesty-skeleton, where the SDK packages are already installed and whose download and installation is described in a separate chapter [Installation](../get-started/installation.md). We can also use the SDK in any standalone application we want to integrate with Orchesty. Communication with the orchestration layer is completely independent. The Orchestration layer itself can also be used [as a service](https://orchesty.io/services).

:::tip
There are two ways to integrate the service using Orchesty:
- Install the SDK into the service and register the service to the orchestration layer in **Orchesty Admin**, see [SDK settings](../tutorials/SDK-settings.md).
- Use the SDK to create connectors for the service and integrate it using its API.
:::

## SDK installation outside Orchesty-skeleton

<Tabs>
<TabItem value="nodejs" label="Node.js">

**Nodejs-SDK** is available as a [public package](https://www.npmjs.com/package/@orchesty/nodejs-sdk). To start the installation, run `npm install @orchesty/nodejs-sdk`.

</TabItem>
<TabItem value="php" label="PHP">

**PHP-SDK** is available as a public [package](https://packagist.org/packages/orchesty/php-sdk). To install, use the [Composer](https://getcomposer.org/) tool. Run the `composer require orchesty/php-sdk` command.

Next, you need to register the Symfony bundle. Add the following line to the `php-sdk/config/Bundles.php` file:

```php
./config/Bundles.php

    HbPFApplicationBundle::class     => ['all' => TRUE],
    HbPFBatchBundle::class           => ['all' => TRUE],
    HbPFCommonsBundle::class         => ['all' => TRUE],
    HbPFConnectorBundle::class       => ['all' => TRUE],
    HbPFCustomNodeBundle::class      => ['all' => TRUE],
```

The last step is to register the applications and connectors as a Symfony service.

```yaml - title="./config/services.yaml"
    node_services_dirs:
        - '%kernel.project_dir%/config'

    applications:
        - '%kernel.project_dir%/config'
```
</TabItem>
</Tabs>

You still need to register the application with the installed SDK package in **Orchesty Admin**. This will start communicating with Orchesty and its actions will be available in the topology editor. More information can be found in the [SDK settings](../tutorials/SDK-settings.md) tutorial.
