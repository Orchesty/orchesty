# Changelog

All notable changes to Orchesty Community Edition are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [3.0.3] - 2026-11-11

### Added

- **Actions:** Catalog of actions provided by workers (`GET /actions`) with an Actions page in the UI, an authorization
  check before a worker runs an action on behalf of a user (`POST /actions/grant`) and the `actions:run` API token
  scope.
- **Bridge:** Error, pending and stopped events of the main branch (nodes from which a Response node is reachable) are
  reported to synchronous callers, so they no longer wait for a timeout.
- **Starting Point:** `run-sync` endpoints that start a topology and wait for its main branch. They return the Response
  payload (200), an error (422), a run waiting for a person (202) or a run that stopped without a result (204).
  Configured by `SYNC_TIMEOUT`, `SYNC_WAIT_LIMIT` and `SYNC_REPLY_BASE_URL`.
- **UI:** Copy URL dialog in the topology designer with async and sync start URLs for the active and the strict
  topology version, and a warning when the main branch can return more than one sync result.

### Fixed

- **Bridge:** Bridge did not wait for in-flight messages on shutdown because the topology timeout was read as
  nanoseconds instead of seconds, so unacknowledged messages were redelivered after every restart. Docker Compose
  topologies also get a `stop_grace_period` of the longest node timeout plus 10 seconds.

## [3.0.2] - 2026-11-05

### Added

- **UI:** Failed message links (`trashId`) open the failed message detail directly.
- **UI:** Quick worker filter in the topology editor action menu.

### Changed

- **All services:** PHP 8.4.27, Node.js 26.13.0 and updated Go, PHP and Node.js dependencies.
- **Backend:** System and OAuth2 topologies are updated for Orchesty 3.0.
- **Bridge, Counter:** The process finish time is calculated from the moment the Bridge processed the message.

### Fixed

- **Bridge:** Bridge kept counting failures of a worker that had already recovered. The counter now resets after 10
  minutes without failures.

## [3.0.1] - 2026-08-06

### Added

- **Backend:** `CreateMetricsExpireIndexCommand` that creates expiration indexes for metrics collections.
- **Backend, UI:** Starting a topology returns its `correlationId`, and the UI uses it to open the started run.
- **UI:** Progress bar while topologies are being published.

### Changed

- **Backend:** Deleting a topology also removes its messages waiting in Limiter and Repeater.
- **Backend:** Faster process queries, user task accept / reject and user task removal, and fewer MongoDB indexes. New
  `MONGODB_TIMEOUT`.
- **Counter:** Faster processing (`APP_COMMIT_DELAY_MS`, `APP_ENABLE_SUBPROCESS_COUNTERS`).
- **UI:** The new UI replaced the legacy UI and moved to `app-ui`.
- **UI:** A topology has to be published before its first run.
- **Worker API:** Worker logs go directly to Loki, and the service uses fewer MongoDB indexes.

### Removed

- **Fluentd:** Remaining Fluentd image and configuration.
- **UI:** Legacy UI.

### Fixed

- **Backend:** Metrics returned different empty time slots on MongoDB 7 and 8.
- **Backend:** Polling of running topologies, which now uses a dedicated index.
- **Bridge:** Message headers were lost when a worker returned a malformed response.
- **Bridge:** Wrong minimum log level.
- **Bridge:** Shared service queues were included in the queue registry.
- **Starting Point:** A queue it published to was not declared.
- **UI:** CRON parameters were not saved correctly.
- **UI:** Pages could scroll outside their borders.
- **UI:** Deleting invited users.
- **UI:** Links to help pages.

## [3.0.0] - 2026-05-14

### Added

- **Backend:** API for the new UI: `/applications`, `/topologies/nodes`, `/processes`, `/processes/<id>`, `/logs`,
  `/user-tasks` and `/metrics/connectors`, `/metrics/processes`, `/metrics/requests`, `/metrics/limits` and
  `/metrics/user-tasks`, with configurable bucket sizes for aggregations.
- **Backend:** Names of deleted topologies and nodes in name lookups, so older metrics stay readable.
- **Backend:** Topology execution source. Breakpoints stop only topologies started from the UI.
- **Backend:** New webhooks flow.
- **Backend:** `MigrateTopologyCommand` that migrates topologies from XML to JSON.
- **Backend:** The Topology class can be extended.
- **Backend:** The same application installed on several workers can be told apart.
- **Backend:** New `INSTANCE_URL_PREFIX` and `EMAIL_DSN`.
- **Backend, Limiter:** Repeater metrics with incoming and outgoing message counts, and Limiter and user task metrics.
- **Bridge:** Bridge stops calling a failing worker for a few seconds and moves a process to failed messages after
  `WORKER_MAX_FAILURES` (default 10) failures.
- **Topology Generator:** Settings for Bridge resources (`BRIDGE_PORT`, `BRIDGE_REQUEST_CPU`, `BRIDGE_REQUEST_MEMORY`,
  `BRIDGE_LIMIT_CPU`, `BRIDGE_LIMIT_MEMORY`).
- **UI:** New UI written from scratch in Vue.js, shipped next to the legacy UI:
  - sign-in, password reset, password change, initial setup and user invitations
  - dashboard with heatmap, metrics and lazy-loaded tabs
  - topology designer with metrics on the canvas, breakpoints and topology import with worker mapping
  - applications with form validation, OAuth and activation flow
  - scheduled tasks with crontab validation and next run preview
  - failed message view with message detail, message removal and process termination
  - process detail drawer and a direct link to every process
  - in-app help

### Changed

- **All services:** `hanaboso/mongo-data-grid` ^2.0 and PNPM 11.
- **Backend:** Enabling a topology version disables all its other versions.
- **Backend:** Node metrics use the worker processing time without the time spent in the queue.
- **Backend:** Connector and limit metrics are grouped by node name.
- **Backend, Counter, CRON, Limiter:** MongoDB indexes are created the same way in all services, and missing Limiter
  indexes are added.
- **UI:** Application forms without fields are hidden.

### Removed

- **Backend:** ACL support. Access control is part of Orchesty Enterprise.
- **Backend, Topology Generator:** `WORKER_DEFAULT_PORT` and the `WORKER_DEFAULT_*` resource settings of the topology
  generator.
- **Fluentd:** Removed from deployments. Backend, Bridge, Counter, Limiter and Topology Generator no longer send logs
  over UDP (`UDP_LOGGER_URL` is removed).
- **PHP SDK:** PHP SDK, PHP connectors and comparator are maintained outside this repository.

### Fixed

- **Backend:** The failed messages chart included user tasks.
- **Backend, Limiter:** Wrong current Limiter message count and start time of Limiter metrics collection.
- **Bridge:** The event exchange was not removed correctly.
- **Limiter:** Messages that were processed longer than usual were duplicated.

[Unreleased]: https://github.com/Orchesty/orchesty/compare/3.0.3...master
[3.0.3]: https://github.com/Orchesty/orchesty/compare/3.0.2...3.0.3
[3.0.2]: https://github.com/Orchesty/orchesty/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/Orchesty/orchesty/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/Orchesty/orchesty/compare/2.1.1...3.0.0
