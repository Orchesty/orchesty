import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Starting events

This chapter describes the ways in which you can run processes in topologies. It is always a type of event that can expect a signal or is triggered by a cron or manually. For details on setting up each event, see [editor](../documentation/editor).

## Start event

The start event is a basic element that creates a custom URL and expects an HTTP POST request to start the process.

:::info
The request must be authorized using the **API key** in the `orchesty-api-key` header. The API key can be found in the `.ENV` file.
:::

## Webhook

We can create applications that support webhooks, which provide us with a form to register them. The webhook needs to be assigned to a specific topology in which we use a webhook event, to which the remote service signals are then routed.

:::note Useful links
- [Applications and connectors](../documentation/applications-and-connectors)
- [Tutorial for webhooks](../tutorials/webhooks)
:::

## Cron

For scheduled process execution we use cron event. Its configuration uses crontab write and allows everything that the classic cron offers.

:::note Useful links
- [Scheduled Tasks Tutorial](../tutorials/scheduled-process).
  :::

:::tip
You can read about setting up individual events in [editor](../documentation/editor.md).
:::

## Manual start

Every topology, regardless of the events it uses, can be started manually. To run manually, the topology must be published and active. The manual start option can be found in the action menu of the topology.

![Run btn](/img/documentation/run-btn.png "Run btn")

Manual run also allows input data to be inserted. This makes it great for debugging the topology when modeling it.

![Run modal](/img/documentation/run-modal.png "Run modal")


