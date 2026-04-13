---
title: Control Center
helpId: control-center/overview
order: 1
---

# Control Center

The Control Center gives you a quick overview of the state of running processes and the flow of data through the system.

## Key concept: Process

A **process** is a single end-to-end execution of a topology. Each time a topology is triggered -- by an event, webhook, or cron schedule -- a new process is created. It tracks the data as it flows through all nodes in the topology.

## Overview

Answers two questions at a glance: **are processes running successfully?** and **is the data throughput within limits?**

### Processes heatmap

The heatmap visualizes process activity over time. Each **row** is a topology, each **column** is a time bucket.

- **Green cells** -- successful processes. Darker green means higher volume.
- **Red cells** -- at least one failure in that slot. Click to drill down into the affected processes.

The **All / Failed** toggle filters the view to only show time slots with failures -- useful for quickly spotting problems across many topologies.

### Limiter card

Shows current message throughput vs. the configured limit. Indicates whether the system is operating within capacity or approaching the ceiling.

### Trash card

Total count of failed messages with a breakdown by topology. This is where unprocessed data accumulates and needs attention.

## Applications

Heatmaps grouped by application. Each application has its own heatmap with connectors as rows. This is the fastest way to spot a complete outage of an external service -- if an entire application's heatmap turns red, the service is likely down.

## Connectors

Performance statistics for individual connectors. Shows which endpoints of integrated services are failing, how long their responses take, and what errors they return (4xx, 5xx). Use this tab to diagnose slow or unreliable API endpoints.

## Topologies

Aggregated run statistics for all topologies. Similar to the Overview heatmap but in tabular form with filtering by last activity, run count, and status. Useful for finding inactive or consistently failing topologies.

## Processes

List of all process executions. The **Process Audit** drawer (audit icon on each row) shows a node-by-node execution timeline -- which nodes the data passed through, how long each step took, and where it failed. This is the primary tool for diagnosing individual process failures.

## Limiter

Controls message throughput to prevent overloading external services. When an application has rate limits configured, the limiter queues excess messages and delivers them within the allowed rate. The throughput chart shows actual volume vs. the configured maximum.
