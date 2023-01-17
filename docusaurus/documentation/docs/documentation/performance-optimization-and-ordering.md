import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Performance optimization and ordering

The way we can affect the performance of topologies is by setting the prefetch of each node. The prefetch indicates the number of messages processed in parallel. Setting it to a value greater than 1 will significantly save resources and increase the throughput of the topology. This setting cannot guarantee that the order of messages is maintained.

:::info
**If the topology requires the order of messages to be kept, it is necessary to set the prefetch of all nodes to 1!**
:::

The default prefetch setting is 50. Thus, the message order is not guaranteed in this setting. The default setting can be changed in the topology editor, in the settings of each node.

![Prefetch settings](/img/documentation/prefetch-settings.svg "Prefetch settings")
