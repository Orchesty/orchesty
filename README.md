# Notification Sender
​
## Popis služby
Služba pro správu notifikací Pipes Frameworku. Umožňuje ukládání nastavení, které notifikace budou kam zasílány (email, webhook, RabbitMQ).

## Spuštění služby - development
- `make init-dev` - Spustí aplikaci definovanou v `docker-compose.yml`
- `make test` - Spustí testy

## Konfigurační volby
- SMTP_TRANSPORT
    - Povinný: Ne (výchozí `smtp`)
    - Výběr protokolu pro odesílání emailů
    - Například: `smtp`
- SMTP_HOST
    - Povinný: Ano
    - SMTP server
    - Například: `mailhog`
- SMTP_PORT
    - Povinný: Ano
    - SMTP port
    - Například: `1025`
- SMTP_USER
    - Povinný: Ano
    - SMTP uživatelské jméno
    - Například: `root`
- SMTP_PASSWORD
    - Povinný: Ano
    - SMTP heslo
    - Například: `root`

- MONGO_HOST
    - Povinný: Ne (výchozí `mongodb`)
    - MongoDB server
    - Například: `mongodb`
- MONGO_PORT
    - Povinný: Ne (výchozí `null`)
    - MongoDB port
    - Například: `27017`
- MONGO_USER
    - Povinný: Ne (výchozí `root`)
    - MongoDB uživatelské jméno
    - Například: `root`
- MONGO_PASSWORD
    - Povinný: Ne (výchozí `root`)
    - MongoDB heslo
    - Například: `root`
- MONGO_DB
    - Povinný: Ne (výchozí `notification-sender`)
    - MongoDB databáze
    - Například: `notification-sender`

- RABBIT_HOST
    - Povinný: Ne (výchozí `rabbitmq`)
    - RabbitMQ server
    - Například: `rabbitmq`

## Použité technologie
- PHP 7.4+

## Závislosti
- MongoDB
- RabbitMQ
