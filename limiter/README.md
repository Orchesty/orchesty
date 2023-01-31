# Pipes Limiter


## Popis služby
Limiter je mikroservica, která počítá zda může být požadovaný request na cílový systém proveden, ještě předtím, než se skutečně provede tak, aby jsme zbytečně neatakovali limity, které jsou na daném vzdáleném systému nastaveny.
Díky tomu můžeme předejít různým banům cizích systémů při překračování limitů.

## Spuštění služby - development
- `make ci-test`       - spustí containery, stáhne balíčky a spustí testy

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
