---
title: Failed Messages
helpId: failed-messages/overview
order: 1
---

# Failed Messages

Failed messages are data payloads that could not be processed by a node in a topology. Unlike logs, which are informational records about what happened, failed messages contain the **actual data** that needs to be resolved -- either re-sent for processing or discarded.

A message typically fails when a node encounters an error -- for example, an API returns an error response, data validation fails, or a connection times out. The message is then held in this queue until you take action.

## Table columns

| Column | Description |
|--------|-------------|
| **Topology** | The topology where the failure occurred. |
| **Node** | The specific node that failed to process the message. |
| **Timestamp** | When the failure happened. |
| **Result message** | Error description returned by the node. |

## Filters

- **Search** -- full-text search across messages.
- **Correlation ID** -- filter by a specific process run (see below).
- **Topology** and **Node** dropdowns.
- **Date range** -- limit results to a specific time period.

## Message detail

Click a row to open the detail drawer showing:

- Message metadata (topology, node, correlation ID, timestamp).
- **Headers** -- the message headers as JSON.
- **Body** -- the message payload as JSON.

## Resolution actions

Each failed message can be resolved in three ways:

- **Approve** -- re-sends the message for processing from the point where it failed.
- **Reject** -- permanently discards the message.
- **Edit** -- opens an editor where you can modify the message body before approving. Useful when the data itself caused the failure (e.g., a malformed field).

## Bulk actions

Select multiple rows using the checkboxes for bulk **Approve** or **Reject**. The **More** menu in the header offers **Approve All Filtered** and **Reject All Filtered**, which apply to all messages matching the current filters.

## Correlation ID

Every process run is assigned a unique **correlation ID**. This ID links all messages, logs, and failed messages that belong to the same execution. Use it to trace the complete path of a single process run across pages -- paste it into the Correlation ID filter on this page or the Logs page.
