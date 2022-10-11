# CRON
​
## Popis služby
API pro opakované spouštění topologií Pipes Frameworku.

## Spuštění služby - development
- `make init-dev` - Spustí aplikaci definovanou v `docker-compose.yml`
- `make test` - Spustí testy
- `http://127.0.0.49:8080` - Swagger OpenAPI

## Konfigurační volby
- MONGO_DSN
    - Povinný: Ano
    - MongoDB DSN
    - Například: `mongodb://mongodb/cron`

- MONGO_COLLECTION
    - Povinný: Ano
    - MongoDB kolekce topologií
    - Například: `Cron`

- STARTING_POINT_DSN
    - Povinný: Ano
    - StartingPoint DSN
    - Například: `http://starting-point:8080`

- APP_DEBUG
    - Povinný: Ne (výchozí `false`)
    - Nastavení granularity logování
    - Například: `true` nebo `false`

- ORCHESTY_API_KEY
    - Povinný: Ne (výchozí ` `)
    - Nastavení Orchesty-Api-Key hlavičky
    - Například: `ThisIsNotSoSecret`

- STARTING_POINT_TIMEOUT
    - Povinný: Ne (výchozí `30`)
    - Nastavení timeoutu v sekundách
    - Například: `30`

## Použité technologie
- Go 1.19+

## Závislosti
- MongoDB
- StartingPoint
