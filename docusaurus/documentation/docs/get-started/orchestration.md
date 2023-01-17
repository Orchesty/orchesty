import Image from '/src/components/ThemedImg';
import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Orchestration

The orchestration of integrated services is handled by the orchestration layer. This layer provides us with a number of tools and features, as we will show in our [tutorials](../tutorials/getting-started-with-tutorials.md) and in [documentation](../documentation/overview). In summary, the orchestration layer controls and manages the processes between the integrated services.

## Messaging in Orchesty

The orchestration layer in Orchesty creates message queues between nodes of process topologies using **RabbitMq**. It adds a number of high level functions and business diagnostics. This allows you to flexibly build [process topologies](../documentation/process-topology) that reflect your business needs.

## Process modelling

The processes are modeled using the [graphical editor](../documentation/editor) in [Admin](../get-started/admin). You can create the actions of individual process nodes yourself using the [SDK](../get-started/SDK), or you can use the ready-made collection of applications and connectors in the [Orchesty Store](../get-started/Orchesty-Store).

![Editor](/img/documentation/orchestration-editor.svg "Editor")

## High level features

The orchestration layer handles a number of situations for us that we would otherwise have to handle to keep processes running smoothly. It provides the ability to configure the number and frequency of repeated calls to an unavailable service and to set the behavior according to [status codes](../documentation/results-evaluation).

Topologies can be configured for performance or message order compliance. We can write custom filters and message routing. We can fix the captured non-valid messages in [trash](../documentation/trash) and send them back to the process.


## Rate limiting

A very important tool, especially for cloud service integrations, is [limiter](../documentation/limiter.md). Thanks to it we can configure rate limiting of outgoing messages so that we don't exceed the allowed number of calls to the remote service. Orchesty can monitor the call limits of a specific API across all connectors and topologies.

## Integrations in a multitenant environment

The limiter can even define groups that allow you to distinguish individual user accounts of the called service in each connector and thus monitor the rate limiting of an unlimited number of users using the same process topology. Such a feature is very useful when building integrations in a multitenant environment.

:::tip
Orchesty has an extension [**Applinth**](https://orchesty.io/applinth) for multitenant integrations. It enables advanced management capabilities as well as building your own integration marketplace for SaaS customers.
:::

:::note Useful links
- [Learn with our tutorials](../tutorials/getting-started-with-tutorials.md)
:::
