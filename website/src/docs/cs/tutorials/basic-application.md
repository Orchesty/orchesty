---
layout: main.hbs
collection: documentation
name: Jak vytvořit aplikaci s basic autentizací
parent: Tutorials
level: 2
index: 20

lunr: true
tags: basic application
---


V minulém návodu jsme se naučili vytvořit jednoduchý konektor pro volání bez autorizace. Konektory bez autorizace nebo s Basic autorizací nevyžadují závislost na aplikaci. Tento návod nám ukáže, jak vytvořit vlastní aplikaci, která může zajistit autorizaci a nastavení HTTP hlaviček pro sadu konektorů. Zároveň zprostředkuje formulář pro vložení autorizačního tokenu. Pro tento návod se připojíme ke cloudové službě Sandgrid.

## Co budete potřebovat?
- Pro vytvoření nového konektoru předpokládáme, že máte nainstalované PIPES na svém localhostu. Pokud ne, podívejte se na článek Instalace PIPES a spuštění ukázkového procesu. 
- Připravenou službu s implementovaným balíčkem SDK, registrovanou v PIPES pro přímou integraci. Pokud službu ještě nemáte, podívejte se na kapitolu Jak nastavit vlastní službu s využitím SDK pro přímou integraci s PIPES.


## Vytvoření aplikace s Basic autorizací
Vytvoříme třídu aplikace. 

``` PHP 1
CODE
```
Každá aplikace vytváří objekt do dokumentové databáze, kam můžeme ukládat atributy potřebné především k zajištění autorizace a další uživatelská nastavení, pokud jsou potřebná. Užitečné jsou například limity API uživatelského účtu volané služby. Abychom tato data mohli vkládat, poskytuje aplikace metodu pro vytvoření formuláře, který se následně zobrazí v detailu aplikace v uživatelském rozhraní. Tento formulář nyní vytvoříme.

``` PHP 2
CODE
```
V dalším kroku napíšeme metodu, která připraví data pro sestavení požadavku v konektorech. V této metodě vložíme do požadavku vše, co js společné všem endpointům API integrované služby, tedy nastavení hlaviček a autorizace. V konektorech už pak budeme řešit pouze URL a metodu konkrétního endpointu.

``` PHP 3
CODE
```

``` infoBlock
TODO: Doplnit metodu IsAuthorized
```

## Vytvoření konektoru aplikace
Nyní vytvoříme samotný konektor, který nám umožní odeslat e-mail pomocí aplikace Sendgrid. Vytvoření konektoru jsme si podrobněji popsali v [předchozím návodu](/). Dnes si proto ukážeme hlavně použití aplikace. Nejprve vytvoříme třídu konektoru, které předáme naší aplikaci.

``` PHP 4
CODE
```
Nyní sestavíme požadavek. Konektor bude očekávat data v následujícím tvaru:

``` PHP 5
CODE
```
Pro sestavení požadavku využijeme aplikaci, která nám zajistí nastavení potřebných HTTP hlaviček včetně autorizace. Zadáme tedy pouze URL našeho požadavku a z předaných dat sestavíme body. 

``` PHP 6
CODE
```

Celý konektor pak bude vypadat následovně:

``` PHP 7
CODE
```
Pro detailní popis třídy konektoru doporučujeme prostudovat předchozí návod [Jak vytvořit konektor pro volání REST API](/).

## Použití v procesu
Přihlásíme se do uživatelského prostředí PIPES a vytvoříme nový proces





Nyní už víte, jak vytvořit vlastní aplikaci a rozšířit tak Appstore PIPES o vlastní řešení. V příštím manuálu si ukážeme, [jak vytvořit aplikaci, která využívá autorizaci OAuth1](/). Pokud se chcete raději naučit [jak vytvářet autoriaci s OAuth2](/), můžete tuto kapitolu přeskočit a vrátit se k ní, až budete OAuth1 potřebovat. 