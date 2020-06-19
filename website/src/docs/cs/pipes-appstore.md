---
layout: main.hbs
collection: documentation
name: PIPES Appstore
level: 1 
index: 5
lang: cs
---

Připravili jsme pro vás často používané aplikace a konektory. Tuto knihovnu lze jednoduše zaintegrovat do vaší aplikace.

## Instalace předpřipravených aplikací a konektorů

Rozšíření AppStoru je k dispozici jako veřejný [balíček](https://packagist.org/packages/hanaboso/pipes-connectors). Pro instalaci doporučujeme použít nástroj [Composer](https://getcomposer.org/).

Spuštěním přikazu `composer require hanaboso/pipes-connectors` se balíček stáhne a uloží do vašeho vendoru.

Nyní stačí zaregistrovat Symfony bundle. Do souboru `Bundles.php` přidejte následující řádek:

``` PHP 1

// ./config/Bundles.php

Hanaboso\HbPFConnectors\HbPFConnectorsBundle::class => ['all' => TRUE]
```

Posledním krokem je zaregistrování aplikací a connectorů jako Symfony service.

``` YAML 2

# ./config/services.yaml

node_services_dirs:
    - '%kernel.project_dir%/vendor/hanaboso/pipes-connectors/src/Resources/config'

applications:
    - '%kernel.project_dir%/vendor/hanaboso/pipes-connectors/src/Resources/config'
```
