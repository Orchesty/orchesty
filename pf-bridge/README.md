# Pipes Bridge (PF-Bridge)

## Popis služby
Interní služba tvořící jádro PipesFrameworku. Slouží jako komunikační vrstva mezi jednotlivými orchestrovanými uzly topologií.

Každá topologie obsahuje svojí Bridge, která je spuštěna v samostatném containeru.

## Spuštění služby - development
- `npm start`    - spustí bridge
- `npm install`  - stáhne balíčky pomocí npm

## Konfigurační volby
- NODE_ENV 
    - Povinný: `NE`
    - Environment pod kterým se spouští 
    - Například: `production` - Povolené volby: `production` | `test` | `debug`
- RABBITMQ_HOST 
    - Povinný: `NE`
    - RabbitMQ host
    - Například: `rabbitmq`
- RABBITMQ_PORT 
    - Povinný: `NE`
    - RabbitMQ port
    - Například: `5672`
- RABBITMQ_USER 
    - Povinný: `NE`
    - RabbitMQ user
    - Například: `guest`
- RABBITMQ_PASS 
    - Povinný: `NE`
    - RabbitMQ password
    - Například: `guest`
- RABBITMQ_VHOST 
    - Povinný: `NE`
    - RabbitMQ vhost
    - Například: `/`
- RABBITMQ_HEARTBEAT 
    - Povinný: `NE`
    - RabbitMQ heartbeat
    - Například: `60`
- PERSISTENT_QUEUES 
    - Povinný: `NE`
    - RabbitMQ enable persistent queues
    - Například: `true`
- PERSISTENT_MESSAGES 
    - Povinný: `NE`
    - RabbitMQ enable persistent messages
    - Například: `true`
- METRICS_MEASUREMENT 
    - Povinný: `NE`
    - Measurement for metrics
    - Například: `pipes_node`
- COUNTER_MEASUREMENT 
    - Povinný: `NE`
    - Measurement for counter
    - Například: `pipes_counter`
- METRICS_HOST 
    - Povinný: `NE`
    - Metrics host
    - Například: `influxdb`
- METRICS_PORT 
    - Povinný: `NE`
    - Metrics port
    - Například: `8089`
- UDP_LOGGER_HOST 
    - Povinný: `NE`
    - UDP logger host
    - Například: `logstash`
- UDP_LOGGER_PORT 
    - Povinný: `NE`
    - UDP logger port
    - Například: `5120`
- REPEATER_INPUT_QUEUE 
    - Povinný: `NE`
    - Name of Repeater queue
    - Například: `pipes.repeater`
- REPEATER_CHECK_TIMEOUT 
    - Povinný: `NE`
    - Number for Repeater checking interval
    - Například: `5000`
- MONGO_HOST 
    - Povinný: `NE`
    - MongoDB host
    - Například: `mongo`
- MONGO_PORT 
    - Povinný: `NE`
    - MongoDB port
    - Například: `27017`
- MONGO_USER 
    - Povinný: `NE`
    - MongoDB user
    - Například: ``
- MONGO_PASS 
    - Povinný: `NE`
    - MongoDB pass
    - Například: ``
- MONGO_DB 
    - Povinný: `NE`
    - MongoDB database
    - Například: `repeater`
- PROBE_PORT 
    - Povinný: `NE`
    - Probe port
    - Například: `8007`
- PROBE_PATH 
    - Povinný: `NE`
    - Probe path
    - Například: `/status`
- PROBE_TIMEOUT 
    - Povinný: `NE`
    - Probe timeout
    - Například: `10000`
- MULTI_PROBE_HOST 
    - Povinný: `NE`
    - MultiProbe host
    - Například: `multi-probe`
- TERMINATOR_PORT 
    - Povinný: `NE`
    - Terminator port
    - Například: `8005`
- REDIS_HOST 
    - Povinný: `NE`
    - Redis host
    - Například: `redis`
- REDIS_PORT 
    - Povinný: `NE`
    - Redis port
    - Například: `6379`
- REDIS_PASS 
    - Povinný: `NE`
    - Redis password
    - Například: ``
- REDIS_DB 
    - Povinný: `NE`
    - Redis database
    - Například: `0`
- LIMITER_HOST 
    - Povinný: `NE`
    - Limiter host
    - Například: `limiter`
- LIMITER_PORT 
    - Povinný: `NE`
    - Limiter port
    - Například: `3333`
- LIMITER_QUEUE 
    - Povinný: `NE`
    - Name od Limiter queue
    - Například: `pipes.limiter`
- COUNTER_PREFETCH 
    - Povinný: `NE`
    - Counter prefetch
    - Například: `10`
    
## Použité technologie
- Node.js 10+

## Závislosti
- MongoDB
- RabbitMQ
- Redis
- InfluxDB (optional)
- Logstash (optional)

## Novinky
 - pole followeru zasilané workerem, pokud je vyplněno pole next: [].
 hlavička 
 ```
 pf-worker-folowers: W3siaWQiOiI1ZjYwZGMzYWQzOWQ2MjUzZDk0Yjc0YzQtZGViIiwibmFtZSI6ImRlYnVnIiwidHlwZSI6Indvcmtlci5sb25nX3J1bm5pbmcifV0=
 ```
 
 jedná se o base64 serializin JSON objekt
 ```
    [
        {
            "id":"5f60dc3ad39d6253d94b74c4-deb",
            "name":"debug",
            "type":"worker.long_running"
        }
    ]
 ```
