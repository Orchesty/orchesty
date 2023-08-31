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
    - Například: `mongodb://mongodb/starting-point?connectTimeoutMS=2500&serverSelectionTimeoutMS=2500&socketTimeoutMS=2500&heartbeatFrequencyMS=2500`
- MONGO_COLLECTION
    - Povinný: Ano
    - MongoDB kolekce topologií
    - Například: `Cron`

- APP_DEBUG
    - Povinný: Ne (výchozí `false`)
    - Nastavení granularity logování
    - Například: `true` nebo `false`

## Použité technologie
- Go 1.13+

## Závislosti
- MongoDB
