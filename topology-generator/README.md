# Topology generator

## Popis služby
Interní služba tvořící jádro PipesFrameworku. Slouží ke generování předpisů pro vytvoření nové topologie.

TG obsahuje adaptéry pro spuštění topologií v  Docker-Compose nebo K8S. 
Na základě zvoleného adaptéru je při požadavku na publishování topologie, vygenerován buď docker-cmpose.yml nebo příslušný k8s manifest. 

Topology generator se dále stará i o korektní ukončení běžící topologie.

## Spuštění služby - development
- `make init-dev` - Spustí aplikaci definovanou v `docker-compose.yml`
- `make test` - Spustí testy
- `http://127.0.0.33:8080` - Swagger OpenAPI

## Konfigurační volby
- MONGO_DSN
    - Povinný: `ANO`
    - MongoDB DSN
    - Například: `mongodb://mongodb/topology-generator?connectTimeoutMS=2500&serverSelectionTimeoutMS=2500&socketTimeoutMS=2500&heartbeatFrequencyMS=2500`
- MONGO_TOPOLOGY
    - Povinný: Ne (výchozí `Topology`)
    - MongoDB kolekce topologií
    - Například: `Topology`
- MONGO_NODE
    - Povinný: Ne (výchozí `Node`)
    - MongoDB kolekce uzlů
    - Například: `Node`
- DEPLOYMENT_PREFIX
    - ??
    - Například: `demo`
- GENERATOR_NETWORK 
    - Povinný: `ANO`
    - Pod jakou interní docker sití vznikne nově spuštěná topologie
    - Například: `demo_default`
- PLATFORM
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
- RABBITMQ_HOST
    - Povinný: `ANO`
    - Connection string pro připojení do MongoDB
    - Například: `rabbitmq`

## Worker environment
- WORKER_DEFAULT_PORT
    * 8088
- WORKER_DEFAULT_LIMIT_MEMORY
    * 536870912b => 512MB
- WORKER_DEFAULT_LIMIT_CPU
    * 1

## Použité technologie
- Go 1.14

## Závislosti
- Docker-socket
- K8s ??
- MongoDB
- RabbitMQ
