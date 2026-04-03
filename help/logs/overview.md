---
title: Logs
helpId: logs/overview
order: 1
---

# Logs

The Logs page shows log entries generated during topology process execution. Logs are informational records -- they describe what happened during processing but do not contain the actual data payload (for that, see Failed Messages).

## Table columns

| Column | Description |
|--------|-------------|
| **Timestamp** | When the log entry was created. |
| **Topology** | The topology that produced the log. Links to the topology detail. |
| **Node** | The node that generated the entry. |
| **Node ID** | Technical identifier of the node. |
| **Severity** | Log level, color-coded (see below). |
| **Message** | Log message text. |

Each row also shows a **copy** icon for the correlation ID, allowing quick cross-referencing with other pages.

## Severity levels

| Level | Meaning |
|-------|---------|
| **Error** | A processing failure occurred. The node could not complete its operation. |
| **Warning** | A non-critical issue that did not prevent processing but may need attention. |
| **Info** | Standard operational log -- a step completed successfully. |
| **Debug** | Detailed diagnostic information, typically used during development. |

## Filters

- **Search** -- full-text search across log messages.
- **Correlation ID** -- show all logs from a specific process run.
- **Severity** dropdown -- filter by log level.
- **Topology** and **Node** dropdowns.
- **Date range** -- limit results to a specific time period.

## Log detail

Click a row to open the detail modal with the full message text. If the log includes **additional context** (extra key-value data attached by the node), it is displayed below the message.
