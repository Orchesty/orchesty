import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Topology editor

In the topology detail, we have available an editor tool that allows us to model process topologies in the user interface using notation based on BPMN notation.

## Toolbar
The toolbar is the basic element of the editor and provides us with all the elements we need to build the topology. The elements can be easily dragged and dropped onto the canvas.

![Toolbar](/img/documentation/Toolbar.svg "Toolbar")

## Settings bar
Each element used on the canvas has its own settings in the settings bar on the right side. Different elements also have different setting options. See the description of each element for details.

## Topology nodes
The individual elements of the topology are called **nodes**. All nodes are reusable. Some, like [events](../documentation/starting-events), require no additional code and are fully configurable inside the editor.

Other node types, such as **connectors**, **custom nodes**, and **batch**, perform their tasks via [workers](../documentation/workers). These elements allow the assignment of an action created within any service using the [Orchesty SDK](../get-started/SDK). We can also use connectors from the [Orchesty store](../get-started/Orchesty-Store) collection in them. The list of actions available for each node type can be found in their settings bar.

### Start event
Creates a URL on which to expect an HTTP POST request. It can pass any data to the process. The URL is found after saving the topology in the event settings bar.

![Start event settings](/img/documentation/start-event-settings.svg "Start event settings")

### Cron
The name suggests that this event triggers planned processes. We set it by writing it in crontab format. We can also insert input data.

![Cron event](/img/documentation/cron-event.svg "Cron event")

### Webhook
Webhook is used for applications with registered webhooks. The event in the editor has no settings except the name. Webhooks are set directly in the application that allows their registration. There we also select the topologies that will process the webhook signals. Orchesty then routes the signal to the webhook event of that topology.

![Webhooks settings](/img/documentation/webhook-settings.svg "Webhook settings")

:::note Useful links
- [Webhooks tutorial](../tutorials/webhooks)
:::


### Custom node
A custom node represents any action that we write inside the worker. We use it mostly for filters or data transformation.

Setting up a node involves selecting the appropriate worker and the desired action.

Custom node, like connector and batch, has a **Bridge** tab in the settings. Here we can set the **prefetch** and **timeout** of the node. The prefetch value sets the number of messages processed in parallel on the node. The default value for the prefetch setting is 50. Timeout is the maximum time to wait for a worker response. The default value for timeout is 60 sec.

![Custom node settings](/img/documentation/custom-node-settings.svg "Custom node settings")

:::info
Setting prefetch also affects the order of messages in the topology. **If we want to keep the order of messages, we have to set prefetch to 1!** Read more in the chapter [Performance optimization](../documentation/performance-optimization-and-ordering).
:::

### Connector
A connector is a node that allows communication with a remote service through its API. The connector code is again executed by the worker, so the default settings are the same as for the custom node. The connector also contains a [repeater](../documentation/results-evaluation) setting, which ensures repeated calls when the called service is unavailable.

![Repeater settings](/img/documentation/repeater-settings.svg "Repeater settings")

:::info
The repeater can also be set directly in the connector code. All settings are listed in the chapter [results evaluation](../documentation/results-evaluation).
:::

### Batch
Batch is a special node for batch processing. It can be a connector that paginates the source data, or an action that processes the contents of a single page or array of data.

The node has several options for how to process the data and how to send the output. It can split the data array at the output into individual messages that it sends on for processing separately. It can also store the data in storage, or work over a collection of data that was previously stored in a process.

The batch settings in the editor are the same as for the connector.

:::note useful links
- [Batch processing](../documentation/batch.md)
:::

:::info
The collection of data can theoretically be processed by a custom node. By using batch processing you can split the collection or call a remote service with each item in the collection using the [repeater](../documentation/results-evaluation) and [limiter](../documentation/limiter) options.
:::

### User task
A user task is a node that creates a list of messages and allows us to preview, edit, and send or discard them. It is very well suited for topology stepping when modeling it. The preview of the messages is available in the topology details in the **User tasks** tab. It has no settings other than the name.

![User task](/img/documentation/user-task.svg)

### Gateway
The Gateway is very similar to a user task. Its purpose is routing in topology branching. You can read about routing in [separate chapter](../documentation/routing).
