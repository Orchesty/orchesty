---
layout: main.hbs
collection: documentation
name: Jak vytvořit aplikaci s autorizací OAuth 1
parent: Tutoriály
level: 2
index: 40

lunr: true
tags: oauth1 application aplikace
lang: cs
---

V tomto návodu si ukážeme, jak vytvořit integraci se službou, která vyžaduje autorizaci protokolem OAuth 1.0. 

## Co budeme potřebovat?
- Nainstalované PIPES na svém localhostu pro vytvoření nového konektoru. Instalaci můžete provést pomocí návodu [Instalace a spuštění PIPES](/docs/cs/installation).
- Připravenou službu s implementovaným balíčkem SDK, registrovanou v PIPES pro přímou integraci. Pokud službu ještě nemáte, podívejte se na kapitolu [Jak nastavit vlastní službu s využitím SDK pro přímou integraci s PIPES](/docs/cs/tutorials/sdk-settings/).
- Konektor na získání testovacích dat připravený v rámci návodu [Jak vytvořit konektor pro volání REST API](/docs/cs/tutorials/basic-connector).
- Doporučujeme nastudovat návod [Jak vytvořit aplikaci s basic autentizací](/docs/cs/tutorials/basic-application).

## Příprava aplikace
Vytvoříme třídu aplikace, která bude rozšiřovat abstrakci OAuth1ApplicationAbstract.

``` PHP 1

use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract;

final class SampleOAuth1Application extends OAuth1ApplicationAbstract
{

    ... required methods from interface
}
```

Rozhraní OAuth 1 a OAuth 2 aplikace je téměř identické. U OAuth 1 aplikace musíme navíc doplnit metodu `getAccessTokenUrl`.

``` PHP 1

protected function getAccessTokenUrl(): string
{
    return 'https://app.com/oauth/accessToken';
}
```

V příštím návodu se naučíme, [jak používat batch splitter v integračním procesu](/docs/cs/tutorials/batch-splitter).