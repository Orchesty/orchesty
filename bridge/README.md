## Orchesty Bridge

The Orchesty Bridge serves as an orchestration layer for each topology.

#### HOW TO RUN

```bash
# Start docker containers
make init-dev
# Run tests
make fasttest
# Lint
make lint
```

### Worker Failure Handling (5xx Correlation Poison)

When a worker returns a 5xx status code or is unreachable, the bridge implements a graduated failure handling mechanism to prevent infinite retry loops from saturating RabbitMQ.

#### Behavior

1. **Lock & Nack (rounds 1–9):** On each 5xx or connection error, the bridge locks the `host+nodeId` pair for 5 seconds and Nacks the message so RabbitMQ redelivers it. This gives the worker time to recover.

2. **Poison & Trash (round 10):** After 10 consecutive failure rounds for a given `host+nodeId`, the `correlation-id` from the failing message is marked as **poisoned**. The current message is sent to Trash (Failed Messages in the UI) and a warning log with `isForUi: true` is emitted.

3. **Instant Trash:** All subsequent messages with the same poisoned `correlation-id` targeting the same `host+nodeId` are sent directly to Trash without contacting the worker and without the 5-second delay.

4. **Health Probes:** Messages with a *different* `correlation-id` still pass through normally. They act as natural health probes — if the worker responds successfully, the entire poison state for that `host+nodeId` is cleared and normal processing resumes.

5. **TTL Cleanup:** Poisoned entries expire automatically after 10 minutes if no new messages arrive. A background goroutine runs cleanup every 60 seconds.

#### Key properties

| Property | Detail |
|----------|--------|
| Failure counter scope | Per `host + nodeId` — different nodes on the same worker are tracked independently |
| Parallel deduplication | Multiple goroutines failing simultaneously within the same 5s lock window count as a single failure round, not N |
| Poison trigger | `correlation-id` of the message that caused the Nth failure |
| Recovery signal | Any successful response on the same `host+nodeId` clears all poisoned IDs |
| Notifications | Trashed messages flow through the existing trash handling path (stored in MongoDB, notification sent via events queue) |
| TTL | 10 minutes — poisoned entries are removed if no activity occurs |

#### Configuration

| Environment Variable | Default | Description |
|---------------------|---------|-------------|
| `WORKER_MAX_FAILURES` | `10` | Number of consecutive failure rounds before poisoning the `correlation-id`. Set to `0` to disable (original infinite-retry behavior). |

#### Scenario reference

| Scenario | Result |
|----------|--------|
| 5xx, round 1–9 | Lock 5s + Error + Nack (redelivery) |
| 5xx, round 10 | Trash + poison correlationId + Warn log with isForUi |
| Same correlationId after poisoning | Trash immediately (no HTTP call, no delay) |
| Different correlationId, same host+node | Normal HTTP call (health probe) |
| Different node, same host | Independent tracking (not affected) |
| Health probe succeeds | Clear all poisoned IDs for host+node, resume |
| Health probe fails | Start counting for new correlationId |
| No messages for 10 min | TTL cleanup removes poisoned entries |
| Parallel goroutines fail simultaneously | Count as 1 round, not N |
| `WORKER_MAX_FAILURES=0` | Disabled, original infinite-retry behavior |

---

### Message Discard Limits

Three independent limits cause the bridge to discard (ack + drop) messages when exceeded:

1. **Trash Deduplication Limit** — per `nodeId + correlationId + resultMessage`, limits how many identical messages get stored in trash. Excess copies are acknowledged but not persisted. The limit is fetched from the backend API (`limits.trashDuplicationLimit`).
2. **Resource Limit** — when total storage (MongoDB + RabbitMQ disk + Loki) exceeds a configured MB threshold, all incoming messages are discarded before processing.
3. **Message Integrity Limit** — when total message count (limiter documents + RabbitMQ total messages) exceeds a configured count, all incoming messages are discarded before processing.

#### Configuration

| Environment Variable | Default | Description |
|---------------------|---------|-------------|
| `BACKEND_URL` | `""` | URL of the PHP backend (e.g. `http://backend`). The bridge fetches `GET /api/status` every check cycle to read `limits.storageGb` (converted to MB via \*1024), `limits.messages`, and `limits.trashDuplicationLimit`. Empty = all limits disabled. |
| `LIMITER_COLLECTION` | `limiter` | Name of the limiter collection on the bridge's `MONGODB_DSN` database. |
| `METRICS_STORAGE_COLLECTION` | `db_storage_metrics` | Metrics-collector collection for MongoDB storage data. |
| `METRICS_RABBITMQ_COLLECTION` | `rabbitmq_metrics` | Metrics-collector collection for RabbitMQ disk data. |
| `METRICS_LOKI_COLLECTION` | `loki_retention_metrics` | Metrics-collector collection for Loki volume data. |
| `LIMITS_CHECK_INTERVAL` | `60` | Polling interval in seconds for global limit checks. |

#### Behavior

**Global limits (resource + message integrity):**

- A background goroutine fetches limits from the PHP backend (`GET {BACKEND_URL}/api/status`) and polls MongoDB every `LIMITS_CHECK_INTERVAL` seconds (default 60).
- The `storageGb` limit from the backend is converted to MB via \*1024. The `messages` and `trashDuplicationLimit` values are used as-is. On fetch error, the last successfully fetched values remain in effect.
- Resource limit sums the latest `storage_size_mb` (MongoDB), `total_disk_mb` (RabbitMQ), and `total_data_size_mb` (Loki) from the metrics database (`METRICS_DSN`).
- Message integrity limit sums documents in the `limiter` collection and `total_messages` from the latest `rabbitmq_metrics` entry in the metrics database.
- When a limit is exceeded, an atomic flag is set and all incoming messages are immediately acked and dropped (no processing, no storage, no retry).
- A notification is sent once on state transition (OK -> exceeded, exceeded -> OK) via both HTTP to starting-point and RabbitMQ event with type `limit_overflow`.
- If metrics-collector collections don't exist, they are treated as 0 (no false positives).

**Trash deduplication:**

- In-memory tracker keyed by `nodeId|correlationId|resultMessage`.
- When a message would go to trash, the tracker checks the count for its group. If at or over the limit, the message is acked but not stored.
- A one-time notification is sent when a group first hits the limit.
- Entries expire after 10 minutes of inactivity. A cleanup goroutine runs every 60 seconds.
- With multiple bridge instances, counters are per-process. With N instances and a limit of L, up to N*L copies may exist across all instances.

#### Notifications

All limit events trigger notifications through the existing pipeline:
- **Limit exceeded:** Bridge publishes `limit_overflow` events (severity `critical`) to `orchesty.events` exchange and sends HTTP status `limitOverflow` to starting-point. Notifier matches the `limit_overflow` preset. Enterprise sys-worker builds email via `LimitOverflowEmailMapper`.
- **Limit recovered:** Bridge publishes `limit_recovered` events (severity `info`) to `orchesty.events` exchange and sends HTTP status `limitRecovered` to starting-point. Notifier matches the `limit_recovered` preset. Enterprise sys-worker builds email via `LimitRecoveredEmailMapper`.

Notifications fire only on state transitions, never per-message. Each event type has its own notifier throttle key, so recovery notifications are not blocked by prior overflow throttles.

#### Scenario reference

| Scenario | Result |
|----------|--------|
| `BACKEND_URL` empty | Global limits disabled, no background polling |
| Backend returns `limits` with both values 0 | Individual checks skipped, no enforcement |
| Backend unreachable | Last-known limits remain in effect; if never fetched, no enforcement |
| Storage exceeds resource limit | All messages discarded, notification sent once |
| Storage drops below resource limit | Normal processing resumes, recovery notification sent |
| Identical trash messages exceed dedup limit | Excess copies acked but not stored in MongoDB |
| Trash dedup limit hit for a group | One-time notification sent for that group |
| `trashDuplicationLimit` is 0 or not set | Trash dedup disabled, all trash messages stored |
| Metrics-collector not deployed | Metrics collections missing, treated as 0 MB, resource limit never triggers |
