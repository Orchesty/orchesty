---
layout: main.hbs
collection: documentation
name: Integration
parent: Getting started
level: 2
index: 2
lang: en
 
lunr: true
tags: integration
---

 
PIPES offers two basic principles for integration of services that can complement each other. Their usage depends on the purpose of the service within the infrastructure and other circumstances.

 
## Direct integration
 
PIPES provides Direct Integration as the basic integration principle for development. Despite its many advantages it can’t be applied in all cases. Direct integration principle requires the implementation of PIPES SDK package into the integrated service. This package delivers PIPES framework and the communication with PIPES Core into the service. Direct Integration therefore can not be used when we are not able to implement PIPES SDK into our service. 

![](/uploads/src_architecture/direct_integration.png)

 
In Direct Integration, all service communication with the orchestration layer is handled by the PIPES themselves. All we need is to use the SDK package and register the service in [PIPES Admin](/docs/en/admin). Learn more in this [tutorial](/docs/en/sdk-settings).


 ``` infoBlock
 <H3>Advantages:</h3>
 <ul>
<li>Saves  network traffic</li>
<li>No need to build REST APIs </li>
<li>Integrated service directly accessible in the process editor</li>
<li>Allows building Appstore extensions/li>
</ul>
```
More about direct integration with PIPES:
- [PIPES Extensions](/docs/en/extension)
- [PIPES SDK](/docs/en/sdk)
- [Process editor](docs/en/admin/process-editor)
 
[You can follow our tutorials.](/docs/en/tutorials).

 
## Integration through connectors
 
Most services communicate with surroundings via REST API or SOAP. Some services report their events through webhooks. PIPES uses connectors for these types of communication. If we want to communicate with services through connectors, we can use prebuilt connectors from [PIPES Appstore](/docs/en/pipes-appstore), or build custom connectors when necessary. 

![](/uploads/src_architecture/undirect_integration.png)
 
### Applications & Connectors
 
To fully understand the architecture of integrations with PIPES, we need to explain the concepts of **Application** and **Connector**.
 
 
**Connector** is a class (script) that communicates with the interface of the integrated service (most often REST API or SOAP). The SDK framework provides tools to support this communication, such as logging, storing metrics or evaluating responses. The Connector is reusable and always handles one specific endpoint of the integrated service, e.g. “GET users”. We can use connectors within an **Application** or separately.
 
 
**Application** has two main functions. It provides authentication of communication (mostly OAuth 2) and sets all attributes of communication that endpoints within the integrated service have in common. In addition, it creates a form for entering login details and any other user parameters. The Connector uses the Application for building the request.

![](/uploads/src_architecture/app_and_connectors.png)

[Read more about the topic of Applications in an article about Appstore.](docs/en/pipes-appstore).

If you want to learn how to work with Applications and Connectors, we recommend our guidelines:
- [How to create a Connector to call REST API](/docs/en/tutorials/basic-connector)
- [How to create an Application with basic authentication](/docs/en/tutorials/basic-application)
- [How to create an Application with OAuth 2 authorization](/docs/en/tutorials/oauth2-application)

 
## Synchronized & asynchronous calling
 
Most integration tasks chain sequences of multiple consecutive actions. For such tasks it is ideal to use an architecture model called **Pipes and Filters**. This model represents asynchronous processes in which the individual actions are connected by queues.
 
![](/uploads/src_architecture/process.png)

This model appropriately captures the needs of the business assignment, while at the same time being able to overcome most of the challenges that need to be tackled in integration tasks. More about managing these processes with PIPES in the [Orchestration](/docs/en/orchestration) chapter.
 
## Synchronized calling methods
 
Integration layer allows us to use connectors for synchronized calling without the orchestration layer. Applications of the integration layer can contain methods using the same connector that is used with asynchronous processes with the orchestration layer. Those methods transmit data as an HTTP response.
 
![](/uploads/src_architecture/sync_api.png)

/ More about synchronized calling in chapter: [API synchronized calling](/docs/cs/sync-api).

 
## API
 
API endpoints of the integration layer are developed by creating asynchronous processes and applications with methods for synchronized calling. We can use PIPES like **Enterprise Service Bus** or **API Gateway**. It does the authentication of all requests and data transformations. Its usage depends only on the needs of the project.
 
![](/uploads/src_architecture/pipes_api.png)


Following [article](/docs/en/orchestration) deals with service orchestration using PIPES. 

