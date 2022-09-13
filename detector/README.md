# Detector

## Popis služby
Služba pro sběr metrik Pipes Frameworku z RabbitMQ front a jejich ukládání do InfluxDB nebo MongoDB.

## Spuštění služby - development
- `make init-dev` - Spustí aplikaci definovanou v `docker-compose.yml`
- `make test` - Spustí testy

## Konfigurační volby
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

## Použité technologie
- Go 1.13+
​
## Závislosti
- Kapacitor nebo MongoDB
