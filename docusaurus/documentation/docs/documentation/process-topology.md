import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Process topology

Topology is a fundamental element of orchestration. It represents the definition of a process that can contain any actions or connectors to communicate with integrated services.


## Topology creation

We create the topology in [admin](../get-started/admin) using the "+" button in the left sidebar.

![New topology button](/img/documentation/new-topology.svg)

## Topology detail
In the topology detail we find all the tools for modelling it and monitoring its operation. Here we can see the history and running processes, metrics of each node and [logs](../documentation/logs.md) that we define within the topology.

![Topology detail](/img/documentation/topology-overview.svg)

![Topology processes](/img/documentation/topology-processes.svg)

## Topology modelling

In the topology detail we have [editor](../documentation/editor) which allows us to easily **drag and drop** model the process. The notation of the editor is based on **BPMN**. BPMN 2 files can also be imported into Orchesty and used as a basis for topology design.

![Topology editor](/img/documentation/editor.svg)

:::note
Orchesty does not work with BPMN notation exactly and does not use all elements of the notation according to its standard. On the other hand, it defines its own elements to match the needs of data integrations.
:::

## Publishing

The created topology must be published to run. Publishing creates a container with a control microservice and queues between the topology nodes. The new topology is published in the disabled state, making it functional but not receiving any messages.

## Enable/Disable

Each published topology can be easily activated and deactivated. The disable action tells the start event not to receive any signals. Also, [Cron](../documentation/starting-events) does not start new processes. Already started processes continue their processing.

## Versioning

Any modification of an already published topology automatically creates a new version of it. Thus, the modification will never affect currently running processes. If a new version of the topology is published and activated, new processes are already routed to the new version. The original version is still running and the active process instances continue to be processed.

## Export/Import

Topologies can be exported and imported as an XML file. In this way, for example, topologies can be transferred between development and production environments.

## Test
Use the **Test** button in the action menu to check the availability of all [workers](../documentation/workers.md) used by the topology.

:::tip
For a better understanding of how to work with topologies, we recommend you check out our [tutorials](../tutorials/getting-started-with-tutorials.md).
:::




