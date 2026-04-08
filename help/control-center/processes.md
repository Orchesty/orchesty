---
title: Processes
helpId: control-center/processes
order: 5
---

# Processes dashboard

The Processes tab lists all process executions across topologies.

## Table columns

| Column | Description |
|--------|-------------|
| **Topology** | Topology name with version. Links to the topology detail page. |
| **Start time** | When the process started. |
| **Duration** | How long the process took. |
| **Status** | Completed (green), Running (blue), or Failed (red). |
| **Error message** | Error description if the process failed, otherwise empty. |

## Filters

- **Quick filter**: All / Completed / Running / Failed.
- **Topology** dropdown to show processes for a specific topology.
- **Date range** shared with the dashboard time filter.
- **Refresh** button to reload the data.

## Process Audit

Click the audit icon on a row to open the **Process Audit** drawer. It displays a node-by-node execution timeline showing which nodes the data passed through, how long each step took, and the status at each point. Use this to diagnose where a process slowed down or failed.
