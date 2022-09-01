import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Orchesty SDK

SDK balíčky Orchesty patří vzhledem k architektuře platformy do integrační vrstvy. Mají 2 klíčové funkce:

- Poskytují framework pro tvorbu konektorů a aplikací.
- Zajišťují komunikaci mezi integrovanou službou a orchestrační vrstvou.

Se všemi funkcemi a jejich použitím se seznámíme v [našich návodech](../tutorials/getting-started-with-tutorials.md). 

## SDK v Orchesty-skeletonu

SDK můžeme využív v rámci Orchesty-skeletonu, kde jsou již SDK balíčky nainstalované a jehož stažení a instalace je popsaná v samostatné kapitole [Installation](../get-started/installation.md). SDK můžeme ale využít také v jakékoliv samostatné aplikaci, kterou chceme integrovat pomocí Orchesty. Komunikace s orchestrační vrstvou je zcela nezávislá. Orchestrační vrstvu Orchesty lze využít i jako službu prostřednictvím **Orchesty cloud**.

:::tip
V podstatě exitstují dva způsoby, jak integrovat službu pomocí Orchesty:
- Instalovat SDK do dané služby a službu registrovat k orchestrační vrstvě v **Orchesty Adminu**, viz [SDK settings](../tutorials/SDK-settings.md).
- Pomocí SDK vytvořit konektory pro danou službu a integrovat ji pomocí jejího API.
:::

## Instalace SDK mimo Orchesty-skeleton

<Tabs>
<TabItem value="nodejs" label="Node.js">

**Nodejs-sdk** je k dispozici jako [veřejný balíček](https://www.npmjs.com/package/@orchesty/nodejs-sdk). Instalaci spustíte přikazem `npm install @orchesty/nodejs-sdk`.

</TabItem>
<TabItem value="php" label="PHP">

Orchesty Store je k dispozici jako veřejný [balíček](https://packagist.org/packages/orchesty/php-connectors). Pro instalaci použijeme  nástroj [Composer](https://getcomposer.org/). Spuštěním přikazu `composer require orchesty/php-connectors` v adresáři `php-sdk` se balíček stáhne a uloží do našeho vendoru.

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

Aplikaci s instalovaným SDK balíčkem je ještě nutné zaregistrovat v **Orchesty Admin**. Tím začne s Orchesty komunikovat a její akce budou dostupné v editoru topologií. Více lze nastudovat v návodu [SDK settings](../tutorials/SDK-settings.md).
