# InfluxDB
​
## Popis služby
Databáze pro ukládání časových řad využívaná pro ukládání metrik Pipes Frameworku. Využívá oficiální docker image doplněný o konfigurační soubory z `influxdb.conf`.

## Spuštění služby - development
- `make docker-build` - Vytvoří předkonfigurovaný docker image

## Konfigurační volby
- INFLUXDB_DB
    - Povinný: Ano
    - Automatické vytvoření databáze se zadaným jménem
    - Například: `pipes`
​
## Použité technologie
- InfluxDB
​
## Závislosti
