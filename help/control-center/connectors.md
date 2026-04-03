---
title: Connectors
helpId: control-center/connectors
order: 3
---

# Connectors dashboard

The Connectors tab lists all connectors across topologies with their performance metrics.

## Table columns

| Column | Description |
|--------|-------------|
| **Application** | The application the connector belongs to. |
| **Connector** | Node name within the topology. |
| **Avg request time** | Average response time in milliseconds, with a proportional bar for visual comparison. |
| **Requests** | Total number of requests in the selected time range. |
| **Status 400** | Count of HTTP 4xx errors. Highlighted when above zero. |
| **Status 500** | Count of HTTP 5xx errors. Highlighted when above zero. |
| **Last request status** | HTTP status code of the most recent request, color-coded: green (2xx), yellow (4xx), red (5xx). |

## Filters

- **Quick filter**: All / OK / Errors -- shows only connectors with errors or all.
- **Application** and **Node** dropdowns to narrow down results.
- **Date range** shared with the dashboard time filter.

Click the magnifying glass icon on a row to open the connector detail view.
