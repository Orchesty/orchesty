import Image from '/src/components/ThemedImg';
import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';


# Integration

The orchestration layer deals with where data goes and under what conditions, while the integration layer deals with communication between services and the orchestration layer. It enables the building of integration process components, such as connectors, data transformations, or filters.

## Direct integration

Direct Integration is the basic integration principle for development. It requires the implementation of [Orchesty SDK](../get-started/SDK.md) package into the integrated service. This package delivers Orchesty framework and the communication with [orchestration layer](../get-started/orchestration.md) into the service.

In such a service, we can create custom actions that are then available in the process editor. Using the installed SDK, we can orchestrate any services or microservices.

<Image path="/img/architecture/direct-integration.svg" lightOnly />

:::tip Direct integration
<ul>
    <li>Saves  network traffic </li>
    <li>No need to build REST APIs </li>
    <li>Integrated service directly accessible in the process editor </li>
    <li>Allows building Orchesty Store extensions </li>
</ul>
:::


:::note Useful links
- [About SDK and its installation](../get-started/SDK.md)
- [How to register service for direct integration](../tutorials/SDK-settings.md)
- [How to create custom code for our processes](../tutorials/custom-node.md)
:::

## Integration through API

Most services communicate with surroundings via **REST API**, **GraphQL** or **SOAP**. Some services report their events through **webhooks**.
Orchesty uses connectors for these types of communication. If we want to communicate with services through connectors,
we can use prebuilt connectors from [Orchesty Store](../get-started/Orchesty-Store), or build custom connectors when necessary.

<Image path="/img/architecture/api-integration.svg" lightOnly />

## Applications & Connectors

To fully understand the architecture of integrations, we need to explain concepts of **Application** and **Connector**.

**Connector** is a class that communicates with the API of the integrated service.
The SDK framework provides tools to support this communication, such as logging, storing metrics or evaluating responses.
The Connector is reusable and always handles one specific endpoint of the integrated service, e.g. “GET users”.
We can use connectors within an **Application** or separately.

**Application** has two main functions. It provides authentication of communication (mostly OAuth 2)
and sets all attributes of communication that endpoints within the integrated service have in common.
In addition, it creates a form for entering login details and any other user parameters.
The Connector uses the Application for building the request.
 
<Image path="/img/architecture/app-connectors.svg" lightOnly />

:::note Useful links
- [Using the connector library](../get-started/Orchesty-Store.md)
- [Create a Connector to call REST API](../tutorials/basic-connector)
- [Create an Application with basic authentication](../tutorials/basic-application)
- [Create an Application with OAuth 2 authorization](../tutorials/oauth2-application)
- [Webhooks implementation in to an application](../tutorials/webhooks.md)
:::

