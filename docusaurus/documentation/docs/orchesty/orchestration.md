import Image from '/src/components/ThemedImg';

# Orchestration

The orchestration layer allows modeling data flow traffic with an emphasis on data performance and security.
It creates and controls integration processes above/over the infrastructure application layer.
Every process is a sequence of actions connected through queues.

## Process manager
[Admin](../admin/admin.md) UI serves as a process manager. It allows us to create processes, manage them,
and monitor their traffic. It shows the comprehensive overview of infrastructure, data flows as well as
logs and metrics of running processes.

<Image path="/img/orchestration/manager.png"/>

More about UI in the [Admin](../admin/admin.md) article.

## Process modeling

The process is designed and modeled in the [graphical editor](../admin/process-editor).
Process notation is based and compatible with the Business Process Modelling notation (BPMN) of version 2.0.
The process topology can be prepared in any tool with BPMN 2.0 support and imported into the Orchesty
graphical editor afterwards.

<Image path="/img/orchestration/editor.png"/>

We create the process topology and add the executive codes to its actions. These may be included in the service
we have created and connected to the orchestration layer through the direct integration method.
It also involves connectors for services we want to communicate with via API, which will be the casefor most
SaaS services. More about [Integration](integration) in the previous chapter.

### Recommended links for process modeling theme

- [Process editor](../admin/process-editor)
- [Set up your own service using SDK for direct integration](../tutorials/SDK-settings)
- [Building the first process](../tutorials/first-process)

## Traffic & Process versioning

Having a process ready, we can publish it, thus creating separate Docker containers with control service.
Each process has its own independent control service.

<Image path="/img/architecture/management-services.png"/>

When editing a running process, a new version of topology is automatically created and published in the **disabled**
state. If multiple versions of the process are switched to **enabled**, new messages are automatically routed only
to the latest version. You can switch between versions in this way at will, without losing or duplicating
messages that are routed into them.

## Metrics

Orchesty records metrics of all process instances, size of queues before each process action, duration of instance
processing in each action, CPU time, and response times of an integrated service request.
Collected metrics can be used to find the bottlenecks and optimize running processes.

<Image path="/img/orchestration/metrics.png"/>

## Logs
Configuration allows choosing between logging into ELK stack or MongoDb. It is possible to save logs on multiple levels.
Admin displays logs of a higher level which are mostly used for information with business value.
To display all detailed logs, it is necessary to use other data visualization tools such as Kibana, ELK stack, etc.

<Image path="/img/orchestration/logs.png"/>

For more information about logging options, see the [documentation](../documentation/logs).

## Notifications

Orchesty allows notification settings for all sorts of events. **Status Service** sends notifications
after finishing every process, receives a message with its report. You can configure notification recipients
in the Admin interface. Notifications can be sent to given URL or e-mail addresses.

<Image path="/img/orchestration/notification.png"/>

### Other useful links

- [Notifications docs](../documentation/notifications)
- [How to set notification sending](../tutorials/notifications)

<Image path="/img/architecture/layers.png"/>
