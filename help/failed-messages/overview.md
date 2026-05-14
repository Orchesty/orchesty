---
title: Failed Messages
helpId: failed-messages/overview
order: 1
---

# Failed Messages

Failed messages capture data payloads that could not be processed by a node in a topology. Unlike logs, which only describe what happened, failed messages hold the **actual data** -- the request body and headers at the exact point of failure. This makes them both a diagnostic tool and a recovery mechanism: you can inspect why processing failed, fix the data if needed, and re-send it into the topology from the point where it broke.

A message typically fails when a node encounters an error -- an API returns an error response, data validation fails, a connection times out, etc. The message stays in this queue until you resolve it.

## Actions

Each failed message can be resolved in one of three ways:

- **Approve** -- re-sends the message for processing from the node where it failed. The topology picks up exactly where it left off.
- **Reject** -- permanently discards the message. Use when the data is no longer relevant or the failure was expected.
- **Edit & Approve** -- opens the message body in an editor so you can fix the data before re-sending. Useful when the payload itself caused the failure (e.g., a malformed field, wrong ID, missing required value).

Bulk actions allow you to approve or reject multiple messages at once, including all messages matching the current filters.

## Storage impact

Failed messages are stored in MongoDB and count against the instance's allocated storage. A large backlog of unresolved messages can consume significant disk space, especially when payloads are large. Monitor the trash count regularly and resolve or reject messages to keep storage usage under control. In cloud instances, storage is capped by the plan limit (`ORCHESTY_LIMIT_STORAGE_GB`).

## Duplicate failure guard

When the same node fails the same way many times inside a single process run -- typically a long batch hitting an unavailable upstream API -- the platform keeps the first N identical failures and drops further duplicates instead of flooding this page. A single heads-up notification is sent the moment the threshold is hit so the team learns about the runaway without seeing thousands of identical rows.

The recommended response is to **rerun the affected process** once the upstream is back, not to resolve individual duplicates. The kept entries remain available for analysis (one is enough to diagnose the cause).

What you see on this page when the guard kicked in:

- The count of failed messages for the process can be lower than the actual number of failed steps.
- There is exactly one notification per `node + correlation-id + error message` group, not one per dropped message.
- A duplicate group expires automatically after 10 minutes without new failures, so the same problem hitting later starts counting again from zero.

Defaults:

- **Cloud instances** -- guard is on. The current platform-wide threshold is **1,000**; per-plan tuning is planned.
- **Self-hosted (Community / Enterprise)** -- off by default. Set `ORCHESTY_LIMIT_TRASH_DUPLICATION` on the platform deployment to a positive integer to turn it on.

## Correlation ID

Every process run is assigned a unique **correlation ID**. This ID links all messages, logs, and failed messages that belong to the same execution. Use it to trace the complete path of a single process run across the Failed Messages and Logs pages.
