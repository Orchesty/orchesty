# Orchesty App Store

## Popis služby
App Store je doplněk, který rozšiřuje Orchesty/PHP-Sdk o možnost psaní aplikací.

App Store poskytuje abstrakce pro správu a subscribe webhooků, rozhranní pro budování appstoru pro libovolný UI framework.

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

## Použité technologie
- PHP 8.1+

## Závislosti
- Orchesty/PHP-SDK
- MongoDB
- MariaDB (optional)
- RabbitMQ (optional)
- InfluxDB (optional)
- Redis (optional)
