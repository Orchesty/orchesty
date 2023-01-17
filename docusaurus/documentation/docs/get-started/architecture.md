import Image from '/src/components/ThemedImg';
import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Architecture and development

Orchesty is a microservice platform whose individual services run in a virtualized environment of **Docker containers**.
The whole system is designed to be scalable and extensible/expandable by connectors, custom codes or microservices.

Orchesty as an integration tool creates 2 basic layers - the **integration layer** and the **orchestration layer**. Each has its own importance and together they form an architecture that isolates the responsibility of the individual integrated services from the logic of the processes they participate in.

![Layers](/img/architecture/architecture-layers.svg "Orchesty layers")

## Integration layer

The integration layer is created using [SDK packages](../get-started/SDK.md) and contains the data transformation codes and connectors we create to communicate with the APIs of the integrated services. This is a **code based** part of the project that can be built with the comfort we are used to in normal development projects. Orchesty is not limited by its development environment in our work. We can use our own IDE and versioning in repositories of our choice. Our helper is the framework included in the SDK packages.

## Orchestration layer

The orchestration layer is used to model and manage asynchronous processes that we compose from the individual components of the integration layer. It creates message queues between process nodes using **RabbitMq**. It adds high-level functionality and business diagnostics to the queues, greatly speeding up process building and facilitating subsequent process management.

## Orchesty Admin

To work with the orchestration layer, we use the Orchesty Admin user interface, where we find tools for modelling, controlling and diagnosing processes.
 
:::note Useful links
- [Integrations](../get-started/integration.md)
- [Orchestration](../get-started/orchestration.md)
- [Orchesty Admin](../get-started/admin.md)
:::

## Skeleton repository

To help you get started with Orchesty faster, we've prepared [Orchesty-skeleton](https://github.com/Orchesty/orchesty-skeleton) with the project base. The skeleton contains default directories with [SDK packages](../get-started/SDK.md), where we can start building our orchestration layer services. It's up to us which SDK we want to use.

Within the skeleton mono-repository, we can build any number of services for [Direct Integration](../get-started/integration.md) by copying the contents of the folder with the chosen SDK to a folder at the same level.

```- title="mono-repository folder structure"
my-app/
├── php-sdk/
│   ├── bin
│   ├── config
│   ├── ...
│   ├── Makefile
│   └── ...
└── nodejs-sdk/
│   ├── bin
│   └── ...
└── my-new-service/
│   ├── bin
│   └── ...
└── docker-compose.yml
```

Then add the new service to **docker-compose.yml**.

```yaml title="docker-compose.yml"
services:
   my-new-service:
       image: my-new-service/image:tag
       user: ${DEV_UID}:${DEV_GID}
       working_dir: /var/www
       volumes:
           - ./my-new-service:/var/www:cached
```
:::tip
If you don't have any experience with **docker compose**, we recommend reading the [original documentation](https://docs.docker.com/compose/).
:::

## Integration of services outside the skeleton repository

Any service with the [Orchesty SDK](../get-started/SDK.md) installed can be registered in Orchesty for **direct integration**. We can easily orchestrate a service or microservice architecture across our infrastructure this way.
