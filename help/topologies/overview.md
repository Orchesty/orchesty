---
title: Topologies
helpId: topologies/overview
order: 1
---

# Topologies

A topology is a visual workflow that defines how data flows through a series of processing steps. The Topologies section lets you create, organize, and monitor them.

## Page structure

The page is split into a **sidebar** on the left and a **detail area** on the right. The sidebar shows a folder tree with all your topologies. Selecting a topology opens its detail.

## Folders

Topologies can be organized into folders. Right-click a folder in the sidebar to rename or delete it. Use the folder icon at the top of the sidebar to create a new folder.

## Creating a topology

Click the **+** button at the top of the sidebar. New topologies start in **Draft** state and need to be designed before they can be published and enabled.

## Topology actions

The **More** menu (three dots) in the topology header provides:

- **Rename** -- change the topology name.
- **Move** -- move to a different folder.
- **Clone** -- create a copy.
- **Export** -- download as a `.tplg.json` file.
- **Delete** -- remove the topology.

## Versioning

Each topology can have multiple versions. The **Version History** drawer (clock icon in the header) lists all versions with their state:

| State | Meaning |
|-------|---------|
| **Draft** | Newly created or modified version. Not yet published. |
| **Enabled** | Active version that processes incoming events. |
| **Disabled** | Published but not currently active. |

### Lifecycle

1. **Draft** -- design the flow in the Designer.
2. **Publish** -- validates the flow and makes the version available for activation. Requires at least one starting node connected to processing nodes.
3. **Enable / Disable** -- only one version can be enabled at a time. Enabling a new version automatically disables the currently active one.

## Topology detail tabs

### Topology

Read-only visual editor showing the node flow. Nodes display runtime metrics from the last run (process time, request time, error counts). You can drag nodes to rearrange the layout -- positions are saved automatically.

For a detailed description of node types, see the **Topology Designer** help page.

### Context

MCP manifest editor. The manifest is a JSON document that is validated before saving.

### Processes / Logs / Failed Messages

The same grids as the standalone pages, but filtered to show only data from this topology.

### Metrics

Horizontal bar charts comparing execution times across nodes:

- **Node process time** -- how long each node took to process data.
- **Connector request time** -- how long external API calls took.

Toggle between **Last Run** and **Average** to compare the most recent execution with the historical average.
