import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Workers

Workers we call services that directly interact with the orchestration layer and contain codes for individual topology actions (nodes).

The communication with the orchestration layer is done through the HTTP protocol with a defined interface. This interface is provided by the [Orchesty SDK](../get-started/SDK) packages. These also contain the framework for building connectors and other actions for our topologies.

## Connecting the worker to the orchestration layer

In order for the orchestration layer to start communicating with the worker, the worker must register in Orchesty. To register a worker, go to the Admin tab **Workers** and specify the host and port on which the communication should occur. We can also define any HTTP headers we want to use for communication. This way we can create e.g. basic authorization if the communication takes place outside the secure network. An example of the settings can be found in the [SDK settings](../tutorials/SDK-settings) tutorial.

There is no limit to the number of workers in Orchesty. It's up to us whether we write all actions in one worker, or whether we create workers from our microservices, where each one represents only one action.

## Using worker in topology

Once we register a worker in Orchesty, its actions are made available to us in the topology editor. Each worker action has a name and type defined according to its interface. The selection of actions is available in the settings of the corresponding node ([see chapter editor](../documentation/editor)).

:::note Useful links
- [Orchesty SDK](../get-started/SDK)
- [Process topology](../documentation/process-topology)
- [Editor](../documentation/editor)
- [SDK settings tutorial](../tutorials/SDK-settings.md)
:::




