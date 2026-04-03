---
title: Control Center
helpId: control-center/overview
order: 1
---

# Control Center

The Control Center is the main monitoring dashboard. It provides an overview of system activity across six tabs: **Overview**, **Applications**, **Connectors**, **Topologies**, **Processes**, and **Limiter**.

All tabs share a **time filter** and **refresh** button in the top-right corner.

## Key concept: Process

A **process** is a single end-to-end execution of a topology. Each time a topology is triggered -- by an event, webhook, or cron schedule -- a new process is created. It tracks the data as it flows through all nodes in the topology.

## Overview

The Overview tab gives you a quick picture of what is happening across the system.

### Processes heatmap

The heatmap shows process activity over time. Each **row** represents a topology, each **column** is a time bucket.

- **Green cells** indicate successful processes. Darker green means more processes in that time slot.
- **Red cells** indicate at least one failed process in that slot.
- **Empty cells** (no color) mean no activity.

Hover over a cell to see exact success and failure counts. **Click a cell** to open a processes drawer filtered to that topology and time range.

Use the **All / Failed** toggle above the chart to switch between showing all activity or only time slots that contain failures -- useful for quickly spotting problems.

### Limiter card

Shows the current message throughput vs the configured limit. Green/red arrows indicate whether the actual count is below or above the maximum. Click **View all** to open the Limiter tab.

### Trash card

Displays the total number of failed messages with a breakdown by topology. Click **View all** to navigate to the Failed Messages page.
