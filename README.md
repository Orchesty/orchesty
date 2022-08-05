# Pipes Backend (PipesFramework)

## Popis služby
Backendová service poskytující rozhranní pro Ui Pipes. 
Obsahuje moduly pro práci s BPMN schématem, generování topologií, konfiguraci pipes a další.


## Spuštění služby - development
- `make init`       - spustí containery a nainstaluje balíčky pomocí composeru
- `make test`       - spustí containery, stáhne balíčky a spustí testy
- `make fasttest`   - spustí testy

## Konfigurační volby
- DEV_UID 
    - Povinný: `ANO`
    - ID Uživatele pod kterým se spouští PHP-FPM
    - Například: `${DEV_UID}` - UID se bere ze souboru `.env`
- DEV_GID 
    - Povinný: `ANO`
    - ID Skupiny pod kterým se spouští PHP-FPM
    - Například: `${DEV_GID}` - GID se bere ze souboru `.env`
- KERNEL_CLASS 
    - Povinný: `ANO`
    - Namespace of Symfony Kernel File. 
    - Například: `Hanaboso\PipesPhpSdk\Kernel`
- COMPOSER_HOME 
    - Povinný: `ANO`
    - Cesta k ComposerCache souborům
    - Například: `${HOME}/dev/.composer` - HOME se bere ze souboru `.env`
- PHP_IDE_CONFIG 
    - Povinný: `NE`
    - ID Uživatele pod kterým se spouští PHP-FPM
    - Například: `${PHP_IDE_CONFIG}` - PHP_IDE_CONFIG se bere ze souboru `.env`
- FRONTEND_DSN 
    - Povinný: `ANO`
    - DSN of Frontend.
    - Například: `${DEV_IP}` - DEV_IP se bere ze souboru `.env`
- BACKEND_DSN 
    - Povinný: `ANO`
    - DSN of Backend
    - Například: `${DEV_IP}` - DEV_IP se bere ze souboru `.env`
- REDIS_DSN 
    - Povinný: `ANO`
    - DSN of Redis
    - Například: `redis://redis:6379/10`
- TOPOLOGY_API_DSN 
     - Povinný: `ANO`
     - DSN of Topology Api
     - Například: `topology-api:80`
- MONGODB_DSN 
     - Povinný: `ANO`
     - DSN of Backend
     - Například: `mongodb://mongo`
- MARIADB_DSN 
    - Povinný: `ANO`
    - DSN of Backend
    - Například: `maria`
- STARTING_POINT_DSN 
    - Povinný: `ANO`
    - DSN of StartingPoint
    - Například: `starting-point:80`
- CRON_DSN 
    - Povinný: `ANO`
    - DSN of Cron Api
    - Například: `http://cron-api:5000`
- RABBITMQ_HOST 
    - Povinný: `ANO`
    - RabbitMQ hostname
    - Například: `rabbitmq`
- RABBITMQ_PORT 
     - Povinný: `ANO`
     - RabbitMQ port
     - Například: `5672`
- FTP_API_DSN 
    - Povinný: `ANO`
    - DSN of FTP Api
    - Například: `ftp-api`
- MAILER_API_DSN 
    - Povinný: `ANO`
    - DSN of Mailer Api
    - Například: `mailer-api`
- MAPPER_API_DSN 
    - Povinný: `ANO`
    - DSN of Mapper Api
    - Například: `mapper-api`
- MONOLITH_API_DSN 
    - Povinný: `ANO`
    - DSN of Monolith Api (Same as BACKEND_DSN)
    - Například: `${DEV_IP}` - DEV_IP se bere ze souboru `.env`
- XML_PARSER_API_DSN 
    - Povinný: `ANO`
    - DSN of Mailer Api
    - Například: `xml-parser-api`
- USER_TASK_COLLECTION
    - Povinný: `NE`
    - Jméno userTask mongo collection
    - Default: `userTask`

## Použité technologie
- PHP 7.4+

## Závislosti
- Pipes-PHP-SDK
- Pipes-App-Store
- MongoDB
- RabbitMQ
- InfluxDB
- Redis
- MariaDB (optional)

## Error offsets
https://hanaboso.atlassian.net/wiki/spaces/PIP/pages/77529243/Exceptions
