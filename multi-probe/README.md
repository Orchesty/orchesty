# Pipes Multi Probe

## Popis služby
This microservice to be used for checking API endpoints availability.
In the Pipes Framework it is used for checking HTTP workers statuses.

While app is running:
1. register worker's status endpoint using `add` route, providing the url and topologyId
2. you may list which workers are registered by `list` route
3. You may check all the statuses of workers related to single topology by calling `status` route 

Routes:
- GET probe:8007/topology/list
- POST probe:8007/topology/add
- GET probe:8007/topology/remove?topologyID=XYZ
- GET probe:8007/topology/status?topologyID=XYZ

## Spuštění služby - development
- `make go-test`       - spustí containery, stáhne balíčky a spustí testy

## Konfigurační volby
- REDIS_HOST 
    - Povinný: `ANO`
    - Redus host
    - Například: `redis`
- REDIS_PORT 
    - Povinný: `NE`
    - Redus host
    - Například: `6379`
- REDIS_PASS 
    - Povinný: `NE`
    - Redus host
    - Například: ``
- REDIS_DB 
    - Povinný: `NE`
    - Redus host
    - Například: `0`

## Použité technologie
- GO 1.13+

## Závislosti
- Redis
