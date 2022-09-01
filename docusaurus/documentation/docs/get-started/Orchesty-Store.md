import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Orchesty Store

Orchesty store tvoří jedno z volně dostupných rozšíření Orchesty. Jedná se o kolekci připravených aplikací a konektorů, které lze navíc snadno upravovat a rozšiřovat. Orchesty Store je jednoduše knihovna, vytvořená pomocí SDK. Stejným způsobem můžeme vytvářet vlastní kolekce a používat je napříč našimi projekty. Jak budovat nové aplikace a konektory se naučíme v našich [návodech](../tutorials/getting-started-with-tutorials.md).

## Dostupné konektory

Aktuálně je v Orchesty dostupný store pro PHP a Node.js. Dostupné aplikace a konektory lze ověřit v jejich repozitářích:

- [Node.js repository](https://github.com/Orchesty/orchesty-nodejs-connectors)
- [PHP repository](https://github.com/Orchesty/orchesty-php-connectors)

:::tip
Knihovny pro jednotlivé jazyky nejsou totožné. Pokud  vám chybí pro váš jazyk konektory, které jste našli ve store jiného jazyka, můžete je v projektu používat i současně.
:::

## Installation

<Tabs>
<TabItem value="nodejs" label="Node.js">

Orchesty Store je k dispozici jako [veřejný balíček](https://www.npmjs.com/package/@orchesty/nodejs-connectors). Spuštěním přikazu `pnpm install @orchesty/nodejs-connectors` v adresáři `nodejs-sdk` se balíček stáhne a uloží mezi naše **node_modules**.

:::info
Adresář `nodejs-sdk` platí při použití [Orchesty-skeletonu](../get-started/installation.md), který využívá package manager **pnpm**. Balíček lze samozřejmě instalovat do libovolné aplikace, kterou chcete integrovat pomocí Orchesty.
:::

</TabItem>
<TabItem value="php" label="PHP">

Orchesty Store je k dispozici jako veřejný [balíček](https://packagist.org/packages/orchesty/php-connectors). Pro instalaci použijeme  nástroj [Composer](https://getcomposer.org/). Spuštěním přikazu `composer require orchesty/php-connectors` v adresáři `php-sdk` se balíček stáhne a uloží do našeho vendoru.

:::info
Adresář `php-sdk` platí při použití [Orchesty-skeletonu](../get-started/installation.md). Balíček lze instalovat do libovolné aplikace, kterou chcete integrovat pomocí Orchesty.
:::


Dále je třeba zaregistrovat Symfony bundle. Do souboru `php-sdk/config/Bundles.php` přidáme následující řádek:


```php
./config/Bundles.php

Hanaboso\HbPFConnectors\HbPFConnectorsBundle::class => ['all' => TRUE]
```

Posledním krokem je zaregistrování aplikací a konektorů jako Symfony service.

```yaml - title="./config/services.yaml"
node_services_dirs:
    - '%kernel.project_dir%/vendor/orchesty/php-connectors/src/Resources/config'

applications:
    - '%kernel.project_dir%/vendor/orchesty/php-connectors/src/Resources/config'
```
</TabItem>
</Tabs>

Pro přidání aplikací do marketplace v Orchesty Adminu musí být aplikace registrovaná jako služba v orchestrační vrstvě. Návod k registraci naleznete v kapitole [SDK settings](../tutorials/SDK-settings.md).

Dále je třeba aplikaci a její konektory přidat do kontejneru dané služby. Viz kapitoly [Basic application](../tutorials/basic-application.md) a [OAuth2 application](../tutorials/oauth2-application.md). Takto přidané konektory jsou pak k dispozici pro budování topologií v editoru topologií.
