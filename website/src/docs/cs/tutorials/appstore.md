---
layout: main.hbs
collection: documentation
name: Jak využít konektory z kolekce PIPES Appstore
parent: Tutoriály
level: 2
index: 20

lunr: true
tags: appstore application aplikace
lang: cs
---

V tomto návodu se naučíme využívat kolekci aplikací a konektorů, která je k dispozici v rámci balíčku [PIPES Appstore](/docs/cs/pipes-appstore).


## Co budeme potřebovat?
- Nainstalované PIPES na svém localhostu pro vytvoření nového konektoru. Instalaci můžete provést pomocí návodu [Instalace a spuštění PIPES](/docs/cs/installation).
- Zaregistrovanou výchozí PHP aplikaci s **PHP-SDK** jako službu pro přímou integraci, viz návod [Jak použít vlastní službu s využitím SDK pro přímou integraci s PIPES](/docs/cs/tutorials/sdk-settings/).


## Instalace Appstore
Appstore je k dispozici jako veřejný [balíček](https://packagist.org/packages/hanaboso/pipes-connectors). Pro instalaci použijeme  nástroj [Composer](https://getcomposer.org/). Spuštěním přikazu `composer require hanaboso/pipes-connectors` v adresáři `php-sdk` se balíček stáhne a uloží do našeho vendoru.

Dále je třeba zaregistrovat Symfony bundle. Do souboru `php-sdk/config/Bundles.php` přidáme následující řádek:

``` PHP 1

// ./config/Bundles.php

Hanaboso\HbPFConnectors\HbPFConnectorsBundle::class => ['all' => TRUE]
```

Posledním krokem je zaregistrování aplikací a konektorů jako Symfony service.

``` YAML 2

# ./config/services.yaml

node_services_dirs:
    - '%kernel.project_dir%/vendor/hanaboso/pipes-connectors/src/Resources/config'

applications:
    - '%kernel.project_dir%/vendor/hanaboso/pipes-connectors/src/Resources/config'
```

## Využití konektoru Appstore v procesu
...

## Vlastní rozšíření Appstore
