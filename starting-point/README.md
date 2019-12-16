# Starting Point
​
## Popis služby
API pro spouštění topologií Pipes Frameworku. Topologie je možné spouštět dle jejich ID a názvů. V případě topologií vyžadujících akci uživatele (human tasků) umožňuje také jejich potvrzení nebo zamítnutí.

## Spuštění služby - development
- `make init` - Spustí aplikaci definovanou v `docker-compose.yml`
- `make test` - Spustí testy

## Konfigurační volby
- METRICS_SERVICE
    - Povinný: Ne (výchozí `influx`)
    - Výběr úložiště metrik
    - Například: `influx` nebo `mongo`

- MONGO_HOSTNAME
    - Povinný: Ano
    - MongoDB server
    - Například: `mongodb`
- MONGO_USERNAME
    - Povinný: Ano
    - MongoDB uživatelské jméno
    - Například: `root`
- MONGO_PASSWORD
    - Povinný: Ano
    - MongoDB heslo
    - Například: `root`
- MONGO_DATABASE
    - Povinný: Ano
    - MongoDB databáze
    - Například: `starting-point`
- MONGO_METRICS_DATABASE
    - Povinný: Ne (výchozí `metrics`)
    - MongoDB databáze metrik
    - Například: `metrics`
- MONGO_TOPOLOGY_COLL
    - Povinný: Ne (výchozí `Topology`)
    - MongoDB kolekce topologií
    - Například: `Topology`
- MONGO_NODE_COLL
    - Povinný: Ne (výchozí `Node`)
    - MongoDB kolekce uzlů
    - Například: `Node`
- MONGO_HUMAN_TASK_COLL
    - Povinný: Ne (výchozí `LongRunningNodeData`)
    - MongoDB kolekce uživatelských akcí (human tasků)
    - Například: `LongRunningNodeData`
- MONGO_WEBHOOK_COLL
    - Povinný: Ne (výchozí `Webhook`)
    - MongoDB kolekce webhooků
    - Například: `Webhook`
- MONGO_TIMEOUT
    - Povinný: Ne (výchozí `10`)
    - MongoDB timeout
    - Například: `10`
- MONGO_MEASUREMENT
    - Povinný: Ne (výchozí `monolith`)
    - MongoDB kolekce metrik
    - Například: `monolith`

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
- RABBIT_COUNTER_QUEUE_DURABLE
    - Povinný: Ne (výchozí `true`)
    - RabbitMQ trvanlivost fronty s counterem při restartu
    - Například: `true` nebo `false`
- RABBIT_DELIVERY_MODE
    - Povinný: Ne (výchozí `2`)
    - RabbitMQ způsob ukládání zpráv (1 = transient [RAM], 2 = persistent [RAM + HDD])
    - Například: `1` nebo `2`
- RABBIT_QUEUE_DURABLE
    - Povinný: Ne (výchozí `true`)
    - RabbitMQ trvanlivost fronty s counterem při restartu
    - Například: `true` nebo `false`
- RABBIT_CONCURRENT_PUBLISH_RATE
    - Povinný: Ne (výchozí `32767`)
    - RabbitMQ maximální počet souběžně posílaných zpráv
    - Například: `32767`

- INFLUX_HOSTNAME
    - Povinný: Ne (výchozí `influxdb`)
    - InfluxDB server
    - Například: `influxdb`
- INFLUX_PORT
    - Povinný: Ne (výchozí `8089`)
    - InfluxDB port
    - Například: `8089`
- INFLUX_REFRESH_TIME
    - Povinný: Ne (výchozí `3600`)
    - InfluxDB interval znovunalézání serveru 
    - Například: `3600`
- INFLUX_MEASUREMENT
    - Povinný: Ne (výchozí `monolith`)
    - InfluxDB tag metrik
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
