import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# ProcessDto

ProcessDto is an object for data transfer in topologies. All [worker](../documentation/workers) interfaces always expect this object. ProcessDto consists of two main parts, which we describe below.

## Headers
Headers are used to carry control information such as the required settings of [Repeater](../documentation/results-evaluation) or [Limiter](../documentation/limiter), [routing](../documentation/routing) requests, and other information that is not part of the data being processed. Some of the settings in the headers can be changed within the worker code, others are defined only by the orchestration layer.

:::note
Some of these settings are handled in parallel by means other than message headers. They have their meaning here and the control service prefers specific definitions in specific situations.
:::

## Data
Data is transmitted in Orchesty messages in the form of a string. It is up to us what data format we choose and how we validate the data. To embed a data object in JSON format, **ProcessDto** provides the `setJsonData` or `setNewJsonData` method.
