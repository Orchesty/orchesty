# Detector

## Popis služby
Služba pro sběr metrik Pipes Frameworku z RabbitMQ front a jejich ukládání do InfluxDB nebo MongoDB.

## Spuštění služby - development
- `make init-dev` - Spustí aplikaci definovanou v `docker-compose.yml`
- `make test` - Spustí testy

## Konfigurační volby
- METRICS_SERVICE
    - Povinný: Ne (výchozí `influx`)
    - Výběr úložiště metrik
    - Například: `influx` nebo `mongo`
- TICK
    - Povinný: Ne (výchozí `5`)
    - Frekvence sbírání metrik ve vteřinách
    - Například: `5`
- APP_DEBUG
    - Povinný: Ne (výchozí `0`)
    - Výběr úrovně logování
    - Například: `0`

- RABBIT_HOST
    - Povinný: Ne (výchozí `http://rabbitmq:15672`)
    - RabbitMQ server
    - Například: `http://rabbitmq:15672`+
- RABBIT_USERNAME
    - Povinný: Ne (výchozí `guest`)
    - RabbitMQ uživatelské jméno
    - Například: `guest`
- RABBIT_PASSWORD
    - Povinný: Ne (výchozí `guest`)
    - RabbitMQ heslo
    - Například: `guest`

- METRICS_HOST
    - Povinný: Ne (výchozí `mongo` nebo `kapacitor` dle `METRICS_SERVICE`)
    - MongoDB nebo Kapacitor server
    - Například: `mongo` nebo `kapacitor`
- METRICS_PORT
    - Povinný: Ne (výchozí `27017` nebo `9092` dle `METRICS_SERVICE`)
    - MongoDB nebo Kapacitor port serveru
    - Například: `guest`

- MONGO_DATABASE
    - Povinný: Ne (výchozí `metrics`)
    - MongoDB databáze
    - Například: `metrics`
- MONGO_COLLECTION
    - Povinný: Ne (výchozí `rabbitmq`)
    - MongoDB kolekce
    - Například: `rabbitmq`
- MONGO_TRIES
    - Povinný: Ne (výchozí `3`)
    - MongoDB maximální počet pokusů o připojení
    - Například: `3`

- INFLUX_DATABASE
    - Povinný: Ne (výchozí `pipes`)
    - InfluxDB databáze
    - Například: `pipes`
- INFLUX_MEASUREMENT
    - Povinný: Ne (výchozí `rabbitmq_queue`)
    - InfluxDB tag
    - Například: `rabbitmq_queue`
- INFLUX_RETENTION
    - Povinný: Ne (výchozí `default`)
    - InfluxDB frekvence sběru metrik
    - Například: `default`

## Použité technologie
- Go 1.13+
​
## Závislosti
- Kapacitor nebo MongoDB
