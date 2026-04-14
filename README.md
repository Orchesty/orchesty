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
