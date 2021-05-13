---
layout: main.hbs
collection: documentation
name: Orchestration
parent: Getting started
level: 2
index: 3
lang: en
 
lunr: true
tags: orchestration
---
 
The PIPES orchestration layer allows modeling and data flow traffic with an emphasis on data performance and security. It creates and controls integration processes above/over the infrastructure application layer. Every process is a sequence of actions connected through queues.
 
![](/uploads/src_architecture/layers.png)
 
## Process manager
[PIPES Admin](/docs/en/admin) UI serves as a process manager. It allows us to create processes, manage them, and monitor their traffic. It shows the comprehensive overview of infrastructure and data flows, it displays logs and metrics of running processes.
![](/uploads/scr_orchestration/1_manager.png)

More about UI in the [PIPES Admin](/docs/en/admin) article.
 
## Process modeling
The process is designed and modeled in the [graphical editor](/docs/en/admin/process-editor). Process notation is based and compatible with the Business Process Modelling notation (BPMN) of version 2.0. The process topology can be prepared in any tool with BPMN 2.0 support and imported into the PIPES graphical editor afterwards.

![](/uploads/scr_orchestration/2_editor.png)
 
We create the process topology and add the executive codes to its actions. These may be included in the service we have created and connected to the orchestration layer through the direct integration method. It also involves connectors for services we want to communicate with via API, which will be the case for most SaaS services. Read more about [Integration](/docs/en/integration) in the following chapter.
 
### Recommended links for process modeling theme::
- [Process Editor](/docs/en/admin/process-editor)
- [How to set up your own service using SDK for direct integration with PIPES](/docs/en/tutorials/sdk-settings)
- [Building the first process](/docs/cs/tutorials/first-process)
 
 
## Traffic & Process versioning
Having a process ready, we can publish it, thus creating separate Docker containers with control service. Each process has its own independent control service.
 
![](/uploads/src_architecture/management_services.png)
 
When editing a running process, a new version is automatically created and published in the **disabled** state. If multiple versions of the process are switched to **enable**, new messages are automatically routed only to the latest version. You can switch between versions in this way at will, without losing or duplicating the messages that are routed into them.
 
## Metrics
PIPES records the metrics of all process instances, the size of queues before each process action, the duration of instance processing in each action, CPU time, and the response time of an integrated service request. Collected metrics can be used to find the bottlenecks and optimize the running processes.
 
![](/uploads/scr_orchestration/3_metrics.png)
 
## Logs
PIPES configuration allows choosing between logging into ELK stack or MongoDb. It is possible to save logs on multiple levels. PIPES Admin displays logs of a higher level which are mostly used for information with business value. To display all detailed logs, it is necessary to use other data visualization tools such as Kibana, ELK stack, etc.
 
![](/uploads/scr_orchestration/5_logs.png)
 
For more information about logging options, see the [documentation](/docs/en/documentation/logs).
 
## Notifications
PIPES allows notification settings for all sorts of events. **Status Service** sends notifications and, after finishing every process, receives a message with its report. You can configure notification recipients in the PIPES Admin interface. Notifications can be sent to the required URL or e-mail addresses. 
 
![](/uploads/scr_orchestration/4_notification.png)
 
### Other useful links:
- [Notifications docs](/docs/en/documentation/notifications)
- [How to set notification sending](/docs/en/tutorials/notifications)