---
title: Topology Designer
helpId: topologies/designer
order: 2
---

# Topology Designer

The Designer is a full-screen visual editor for building topology flows. Open it via the **Design** button in the topology header.

Add nodes from the palette on the left, place them on the canvas, and connect them by dragging from an output port to an input port. Click **Save** to persist your changes.

## Node types

Nodes are divided into three categories based on their role in the flow.

### Starting nodes (circle shape)

Starting nodes define how a topology is triggered. Every topology needs at least one.

| Node | Purpose |
|------|---------|
| **Event** | Manual or programmatic trigger. Can be toggled on/off, started manually via **Run**, or called from external systems via its URL. |
| **Webhook** | HTTP endpoint trigger. Same controls as Event -- toggle, Run, Copy URL. Share the URL with external services that need to trigger the topology. |
| **Cron** | Scheduled trigger. Runs automatically on a crontab schedule. The gear icon opens settings where you configure the cron expression. The node displays the next scheduled run time when enabled. |

Starting nodes can be enabled or disabled independently. A disabled starting node will not accept triggers.

### Processing nodes (square shape)

Processing nodes execute the actual work in the flow.

| Node | Purpose |
|------|---------|
| **Connector** | Calls an external service through an installed application. This is the main integration point -- for example, fetching data from an API, sending emails, or writing to a database. Connectors are provided by **workers** and use applications configured in the Applications section. |
| **Batch** | Processes data in batches. Works like a Connector but designed for bulk operations where data is split, processed in chunks, and reassembled. |
| **Custom Action** | Executes custom logic defined in a worker's SDK. Unlike Connectors, Custom Actions do not call external APIs -- they transform, filter, or route data within the system. |

When placing a Connector, Batch, or Custom Action, you assign it an **action** from the dropdown. Actions are grouped by worker and type. If no actions appear, make sure at least one worker is registered in **Settings** and its applications are installed.

### Control nodes

| Node | Purpose |
|------|---------|
| **Breakpoint** | Pauses the process and queues the message for manual review. See the Breakpoints section below. |
| **Annotation** | A text note on the canvas. Does not participate in the data flow. |

## Workers and actions

**Workers** are backend services registered in the Settings section. Each worker provides a set of available actions -- connectors for external integrations, custom actions for internal logic, and batch operations for bulk processing.

When you install an application (e.g., a Slack or Mailchimp connector), its actions become available in the Designer's action dropdown under the worker that hosts it. Custom actions written in the SDK are also listed here.

The relationship is: **Worker** hosts **Applications**, applications provide **Actions**, actions are assigned to **nodes** in the topology.

## Breakpoints

A Breakpoint node pauses the process at that point and holds the message in a queue until it is manually approved.

- A **red badge** on the node shows the number of queued messages.
- **Click the badge** to open the approval modal, where you can inspect the message headers and body, then approve it to continue processing. You can approve messages one by one or all at once.
- **Reject all** is available from the node's context actions in the read-only Topology tab.

Breakpoints are useful for debugging flows during development or for implementing human-in-the-loop approval workflows.

When you start a new process run and there are queued breakpoint messages, they will be cleared automatically.

## Running a topology

To manually trigger a topology, click **Run** on any starting node (Event, Webhook, or Cron) in the read-only Topology tab. You can optionally provide input data as JSON.

After triggering a run, a **process panel** appears on the canvas showing:

- Process status (Running / Completed / Failed)
- Correlation ID
- Start time, end time, and duration

Nodes also display runtime indicators -- process time and request time metrics, as well as error and warning icons if failures occurred.

## Copying trigger URLs

For Event and Webhook nodes, use the **Copy URL** action to get the endpoint URL. This URL can be used by external systems to trigger the topology programmatically.
