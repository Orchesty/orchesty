# Starting Point
​
## Popis služby
API pro spouštění topologií Pipes Frameworku. Topologie je možné spouštět dle jejich ID a názvů.

## Spuštění služby - development
- `make init-dev` - Spustí aplikaci definovanou v `docker-compose.yml`
- `make test` - Spustí testy
- `http://127.0.0.44:8080` - Swagger OpenAPI

## Konfigurační volby
- MONGO_DSN
    - Povinný: Ano
    - MongoDB DSN
    - Například: `mongodb://mongodb/starting-point?connectTimeoutMS=2500&serverSelectionTimeoutMS=2500&socketTimeoutMS=2500&heartbeatFrequencyMS=2500`
- MONGO_TOPOLOGY_COLL
    - Povinný: Ne (výchozí `Topology`)
    - MongoDB kolekce topologií
    - Například: `Topology`
- MONGO_NODE_COLL
    - Povinný: Ne (výchozí `Node`)
    - MongoDB kolekce uzlů
    - Například: `Node`
- MONGO_WEBHOOK_COLL
    - Povinný: Ne (výchozí `Webhook`)
    - MongoDB kolekce webhooků
    - Například: `Webhook`

- RABBIT_HOSTNAME
    - Povinný: Ne (výchozí `rabbitmq`)
    - RabbitMQ server
    - Například: `rabbitmq`
- RABBIT_PORT
    - Povinný: Ne (výchozí `5672`)
    - RabbitMQ port
    - Například: `5672`
- RABBIT_USERNAME
    - Povinný: Ne (výchozí `guest`)
    - RabbitMQ uživatelské jméno
    - Například: `guest`
- RABBIT_PASSWORD
    - Povinný: Ne (výchozí `guest`)
    - RabbitMQ heslo
    - Například: `guest`
- RABBIT_COUNTER_QUEUE_NAME
    - Povinný: Ne (výchozí `pipes.multi-counter`)
    - RabbitMQ název fronty s counterem
    - Například: `pipes.multi-counter`
- RABBIT_DELIVERY_MODE
    - Povinný: Ne (výchozí `2`)
    - RabbitMQ způsob ukládání zpráv (1 = transient [RAM], 2 = persistent [RAM + HDD])
    - Například: `1` nebo `2`

- METRICS_DSN
    - Povinný: Ano
    - Metriky DSN
    - Například: `mongodb://mongodb/starting-point?connectTimeoutMS=2500&serverSelectionTimeoutMS=2500&socketTimeoutMS=2500&heartbeatFrequencyMS=2500` nebo `influxdb://influxdb:8089`
- METRICS_MEASUREMENT
    - Povinný: Ne (výchozí `monolith`)
    - MongoDB kolekce metrik / InfluxDB tag metrik
    - Například: `monolith`

- CACHE_EXPIRATION
    - Povinný: Ne (výchozí `24`)
    - Délka expirace položek cache v hodinách
    - Například: `24`
- CACHE_CLEAN_UP
    - Povinný: Ne (výchozí `1`)
    - Interval vyhodnocování expirace položek cache v hodinách
    - Například: `1`

- APP_CLEANUP_TIME
    - Povinný: Ne (výchozí `300`)
    - Interval spouštění uvolňování paměti
    - Například: `300`
- APP_CLEANUP_PERCENT
    - Povinný: Ne (výchozí `1`)
    - Limit vytížení CPU pro spuštění uvolňování paměti
    - Například: `1`

- GOROUTINE_LIMIT
    - Povinný: Ne (výchozí `2000`)
    - Limit maximálního počtu zpracovávaných požadavků najednou
    - Například: `2000`

## Použité technologie
- Go 1.13+

## Závislosti
- InfluxDB nebo MongoDB
- RabbitMQ
