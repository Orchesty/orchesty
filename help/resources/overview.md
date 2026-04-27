---
title: Resources
helpId: resources/overview
order: 6
---

# Resources

The Resources page is the single place where you can see how much of your plan's capacity is in use, identify what is consuming it, and free it up when you no longer need it.

It tracks three things:

- **Topology slots** -- how many published topologies (bridges) are running.
- **Messages in flight** -- live message volume across RabbitMQ and the limiter.
- **Storage** -- disk usage across MongoDB, RabbitMQ and Loki.

## Topology slots

A **topology slot** is consumed by every published topology version. Publishing a topology starts a dedicated **bridge** container that orchestrates messages between its nodes -- one bridge per published version, one slot per bridge.

The slot count on this page must always match the **Topology slots** card on the Overview dashboard. Both numbers come from the same source: every row with `visibility = published` and `deleted = false` counts.

### What does and does NOT free a slot

| Action | Frees a slot? | Why |
|---|---|---|
| **Disable** the topology | No | Disabling only stops start nodes from accepting new events. The bridge keeps running and the slot stays reserved. |
| **Enable a different version** | No | The newly enabled version's bridge runs alongside the previous version's bridge. Both occupy a slot until the older one is decommissioned. |
| **Decommission** (Resources page) | Yes | Stops and removes the bridge. The topology row stays as Draft so you can re-publish later if needed. |
| **Delete topology** | Yes | Removes the topology entirely. |

### Publishing past the limit

When the plan's slot ceiling has been reached, publishing a new topology (or a new version of an existing one) is blocked with a clear message. Free a slot first by decommissioning an older version on this page, then retry the publish.

## Finding wasted slots

The page makes it easy to spot bridges that hold a slot without doing useful work. Two cards above the grid summarise the state:

- **Topology slots** -- total used vs. plan limit.
- **Reducible** -- count of older versions that have a newer version of the same topology running. These bridges still occupy a slot but no events flow through them, so decommissioning is safe.

Click **Show** on the Reducible card to highlight those rows in the grid. The grid lists every published bridge with:

- Topology name and version
- Status (Enabled / Disabled)
- Running processes (topologies actively executing)
- Trash messages (failed messages waiting in the topology's trash)

Sort by version or filter by name to find candidates for cleanup.

## Available actions per row

Each row's **More** menu offers:

- **Decommission** -- unpublish the bridge and free the slot. The topology row stays as Draft.
- **Delete** -- remove the topology version entirely.
- **Restart** -- restart the bridge container. Mainly useful for local development or after a config change.

For batch cleanup, use **Restart all enabled** (top-right) when you have just rolled out a worker config change and want to ensure every bridge picks it up.

## Messages and Storage history

Below the bridge grid, the **Messages in flight** and **Storage** charts show historical usage over the global time-range filter. Use them to spot capacity spikes that the live counters on the Overview cards may otherwise smooth out.

When either chart approaches the plan limit, a banner is also shown on the top bar (warning at 90%, critical at 100%).
