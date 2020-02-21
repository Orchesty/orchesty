# Topology generator

## Popis služby
Interní služba tvořící jádro PipesFrameworku. Slouží ke generování předpisů pro vytvoření nové topologie.

TG obsahuje adaptéry pro spuštění topologií v  Docker-Compose nebo K8S. 
Na základě zvoleného adaptéru je při požadavku na publishování topologie, vygenerován buď docker-cmpose.yml nebo příslušný k8s manifest. 

Topology generator se dále stará i o korektní ukončení běžící topologie.

## Spuštění služby - development
- `make run` - spustí container definovaný v `docker-compose.yml`
- `make go-test` - spustí testy
- `http://127.0.0.33:8080` - Swagger OpenAPI

## Konfigurační volby
- DEPLOYMENT_PREFIX
    - ??
    - Například: `demo`
- GENERATOR_NETWORK 
    - Povinný: `ANO`
    - Pod jakou interní docker sití vznikne nově spuštěná topologie
    - Například: `demo_default`
- GENERATOR_MODE
    - Povinný: `ANO`
    - Umožňuje přepínat mezi adaptéry
    - Povolené volby: `compose`, `k8s` ??
- GENERATOR_PATH
    - Povinný: `ANO`
    - Umístění generovaných souborů uvnitř kontejneru
    - Například: `/srv/topology`
- PROJECT_SOURCE_PATH
    - Povinný: `ANO`
    - Umístění generovaných souborů vně kontejneru
    - Například: `/usr/user1/pipes/topology`
- MONGO_HOST
    - Povinný: `ANO`
    - Connection string pro připojení do MongoDB
    - Například: `mongodb://mongo`
- MONGO_DATABASE
    - Povinný: `ANO`
    - Databáze do které se TG připojuje
    - Například: `demo`
- RABBITMQ_HOST
    - Povinný: `ANO`
    - Connection string pro připojení do MongoDB
    - Například: `rabbitmq`

## Použité technologie
- Go 1.13+

## Závislosti
- Docker-socket
- K8s ??
- MongoDB
- RabbitMQ