---
title: Scheduled Tasks
helpId: scheduled-tasks/overview
order: 1
---

# Scheduled Tasks

The Scheduled Tasks page lists all cron-based triggers across topologies. It provides a central place to view and manage timed executions.

## Table columns

| Column | Description |
|--------|-------------|
| **Enable** | Toggle switch to activate or deactivate the scheduled task. |
| **Topology** | Name of the topology this task belongs to. Links to the topology detail. |
| **Name** | The node name within the topology. |
| **Crontab** | The cron expression defining the schedule. If the task is enabled but no expression is set, a warning indicator is shown. |
| **Next run** | The next scheduled execution time, calculated from the crontab expression. |

## Managing tasks

- **Enable / Disable** -- use the toggle switch directly in the row.
- **Edit schedule** -- click the gear icon to open the cron settings modal, where you can set the crontab expression and optional parameters.

## Crontab format

Cron expressions use the standard five-field format:

```
minute  hour  day-of-month  month  day-of-week
```

Examples: `*/5 * * * *` (every 5 minutes), `0 8 * * 1-5` (weekdays at 8:00), `0 0 1 * *` (first day of each month at midnight).

The settings modal shows a human-readable description of the expression and the next two scheduled runs for verification.
