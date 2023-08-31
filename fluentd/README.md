# Fluentd

## Popis služby
Služba pro zpracovávání logů Pipes Frameworku a jejich ukládání do MongoDB. Využívá oficiální docker image doplněný o plugin pro možnost ukládání do MongoDB.

## Spuštění služby - development
- `make docker-build` - Vytvoří předkonfigurovaný docker image

## Konfigurační volby
- MONGO_DSN
    - Povinný: Ano
    - MongoDB DSN
    - Například: `mongodb://mongodb/fluentd`
- MONGO_COLLECTION
    - Povinný: Ano
    - MongoDB kolekce logů
    - Například: `Logs`

## Použité technologie
- Fluentd

## Závislosti
- MongoDB
