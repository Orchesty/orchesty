import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';
import DocCardList from '@theme/DocCardList';

# Data Storage
In some cases, it is preferable to store the processed data continuously and work over the stored collection rather than sending all the data through queues. This is especially true for large batches of data and data with complicated downloads from a source or sources. If the process fails at some point, we can save ourselves from having to retrieve the data again.

This approach is much more efficient and cheaper for data transfers, but it is not always appropriate. We use [Cursoring without output](../documentation/batch) and send a message to other nodes in the topology with a reference to the collection only after the entire collection has been traversed.

:::info
The data collection is always only temporary. If we do not remove it, it is deleted automatically after 24 hours.
:::

## DataStorageManager

Data storage is managed by `DataStorageManager`. We get it from the container as follows:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
await container.get<DataStorageManager>(CoreServices.DATA_STORAGE_MANAGER);
```
</TabItem>
</Tabs>

We identify the data collection using the `id` parameter and the optional `app` and `user` parameters, which can be used for more detailed data segmentation, e.g. during integration processes in multitenant environments.

## Storage
We save the data using the `store` method:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
dataStorageManager.store(id, [{ foo: 'bar' }, { john: 'doe' }], app, user);
```
</TabItem>
</Tabs>

## Loading data

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
dataStorageManager.load(id, app, user);
```
</TabItem>
</Tabs>

## Removing a collection

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
dataStorageManager.remove(id, app, user);
```
</TabItem>
</Tabs>

:::note Useful links
- [Stored data tutorial](../tutorials/stored-data)
:::

