---
title: Limiter
helpId: control-center/limiter
order: 6
---

# Limiter dashboard

The Limiter controls message throughput to prevent overloading external services. When an application has rate limits configured, the limiter queues excess messages and delivers them within the allowed rate.

## Throughput chart

The area chart at the top shows total message volume over time. The header displays:

- **Max** -- the configured message limit.
- **Actual** -- the current message count, with a green arrow (below limit) or red arrow (above limit) and the percentage difference.

## Summary by application

A table listing each application with rate limiting configured:

| Column | Description |
|--------|-------------|
| **Application** | Application name. |
| **Limit** | Configured rate (e.g., "100 / 60s") or "off" if no limit is set. |
| **Max (actual)** | Maximum allowed vs actual message count with trend arrows. |
| **Remaining time** | Time until the current rate window resets. |

Click the action icon on a row to view processes for that application.

## Limiter details

A paginated table with per-connector granularity: Application, Connector, Topology (link to detail), Limit, and Max (actual) message counts.
