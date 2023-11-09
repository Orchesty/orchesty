import Image from '/src/components/ThemedImg';

# Integration

Orchesty offers two basic principles for integration of services that can complement each other.
Their usage depends on the purpose of the service within the infrastructure and other circumstances.

## Direct integration

Direct Integration is the basic integration principle for development. Despite it's many advantages it can't be applied in all cases.
Direct Integration principle requires the implementation of Orchesty SDK package into the integrated service.
This package delivers Orchesty framework and the communication with Core into the service.
Direct Integration therefore can not be used when we are not able to implement SDK in service.

<Image path="/img/architecture/direct-integration.png" lightOnly />

In Direct Integration, all service communication with the orchestration layer is handled by the Orchesty themselves.
All we need is to use the SDK package and register the service in [Admin](../admin/admin.md).
Learn more about registration in appropriate [tutorial](../tutorials/SDK-settings).

:::tip

<h4> Direct Integration: </h4>
<ul>
    <li>Saves  network traffic </li>
    <li>No need to build REST APIs </li>
    <li>Integrated service directly accessible in the process editor </li>
    <li>Allows building Appstore extensions </li>
</ul>
:::

## Integration through connectors

Most services communicate with surroundings via REST API or SOAP. Some services report their events through webhooks.
Orchesty uses connectors for these types of communication. If we want to communicate with services through connectors,
we can use prebuilt connectors from [Appstore](../orchesty/app-store), or build custom connectors when necessary.

<Image path="/img/architecture/undirect-integration.png" lightOnly />

### Applications & Connectors

To fully understand the architecture of integrations, we need to explain concepts of **Application** and **Connector**.

**Connector** is a class (script) that communicates with the interface of the integrated service (most often REST API or SOAP).
The SDK framework provides tools to support this communication, such as logging, storing metrics or evaluating responses.
The Connector is reusable and always handles one specific endpoint of the integrated service, e.g. “GET users”.
We can use connectors within an **Application** or separately.

**Application** has two main functions. It provides authentication of communication (mostly OAuth 2)
and sets all attributes of communication that endpoints within the integrated service have in common.
In addition, it creates a form for entering login details and any other user parameters.
The Connector uses the Application for building the request.
 
<Image path="/img/architecture/app-connectors.png" lightOnly />

[Read more about the topic of Applications in an article about Appstore.](../orchesty/app-store)

If you want to learn how to work with Applications and Connectors, we recommend our guidelines:
- [Create a Connector to call REST API](../tutorials/basic-connector)
- [Create an Application with basic authentication](../tutorials/basic-application)
- [Create an Application with OAuth 2 authorization](../tutorials/oauth2-application)

## Synchronized & asynchronous calling

Most integration tasks chain sequences of multiple consecutive actions.
For such tasks it is ideal to use an architecture model called **Pipes and Filters**.
This model represents asynchronous processes in which the individual actions are connected by queues.

<Image path="/img/architecture/process.png" lightOnly />

This model appropriately captures the needs of the business assignment, while at the same time being able
to overcome most of the challenges that need to be tackled in integration tasks.
More about managing these processes with Orchesty in the [Orchestration](../orchesty/orchestration) chapter.

## Synchronized calling methods

Integration layer allows us to use connectors for synchronized calling without the orchestration layer.
Applications of the integration layer can contain methods using the same connector that is used with asynchronous
processes with the orchestration layer. Those methods transmit data as an HTTP response.
 
<Image path="/img/architecture/sync-api.png" lightOnly/>

More about synchronized calling in chapter: [API synchronized calling](../orchesty/sync-api).

## API

API endpoints of the integration layer are developed by creating asynchronous processes and applications
with methods for synchronized calling. We can use Orchesty like **Enterprise Service Bus** or **API Gateway**.
It does the authentication of all requests and data transformations. Its usage depends only on the needs of the project.

<Image path="/img/architecture/pipes-api.png" lightOnly />
