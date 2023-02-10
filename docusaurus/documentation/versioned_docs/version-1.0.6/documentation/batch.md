import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';
import DocCardList from '@theme/DocCardList';

# Batch

To process batches of data, Orchesty offers several options that can be combined in topologies. This results in patterns that are used according to their different options. The basic property of a node is that with the reception of a single message, it can generate any number of messages at the output.

## Batch node
A batch node has several ways it can work with a collection of data. Its default function is to split the collection into individual items. It then sends these to other nodes separately.

## Cursoring
Another feature of the batch node is the ability to iterate over the collection using the cursor. This can be used, for example, when obtaining a collection of data, if you need to get additional data for each item from some source.

The cursor is a parameter of the [ProcessDto](../documentation/processDto) object. We use the `getBatchCursor()` and `setBatchCursor()` methods to handle it. If `ProcessDto` has a BatchCursor set on the Batch node, the node sends a message and calls the retry action [worker](../documentation/workers).

```js
setBatchCursor(cursor: string, iterateOnly = false)
```

## Cursoring without output
By setting the `iterateOnly = true` parameter, the worker will be called repeatedly, but the node will not send a message to the follower. We will use this behavior when working with a collection stored in Data Storage. We will set the parameter to `false` only when the last iteration completes, thus sending a message with the collection data for processing by other nodes in the topology.

:::note Useful links
- [Introduction to batch tutorial](../tutorials/introduction-to-batch)
:::

## Pagination
The cursor is also used in pagination, when we retrieve an unspecified number of pages from the source. The batch node queries the source as long as it retrieves data. The cursor helps us set the number of the requested page.

:::note Useful links
- [Pagination tutorial](../tutorials/pagination)
:::


