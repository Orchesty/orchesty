---
layout: main.hbs
collection: documentation
name: Integrace
level: 1
index: 2

lunr: true
tags: integration
---

PIPES nabízí dva základní principy integrace služeb. Tyto principy se mohou vzájemně doplňovat a jejich použití záleží na účelu služby v rámci infrastruktury a dalších okolnostech. V tomto článku si popíšeme tyto dva principy a jejich využití.

## Přímá integrace
Přímá integrace je základní integrační princip, který PIPES pro vývoj poskytují. Má mnoho výhod, ale není možné ji aplikovat ve všech případech. Při přímé integraci je třeba implementovat v integrované službě balíček PIPES SDK, který dodává framework a komunikaci s PIPES CORE službami. Tím je také dáno omezení tohoto způsobu. Není možné jej využít pro integraci služeb, do kterých nelze SDK implementovat.

![](/uploads/src_architecture/direct_integration.png)

Při přímé integraci spolu komunikují služby s jádrem PIPES **většinou** pomocí HTTP protokolu. Existuje ovšem případ, kdy je komunikace zajištěna prostřednictvím frontovacího protokolu AMQP. Důvodem odlišného způsobu komunikace je využití služeb pro dávkové zpracování typu [Batch splitter](). Naštěstí o způsob komunikace se postarají samotné PIPES. Nám stačí využít framework SDK balíčku a zaregistrovat službu v aplikaci [PIPES Admin](/docs/cs/admin). Jak to udělat se můžete dočíst v [tomto návodu](/docs/cs/sdk-settings).


``` infoBlock
<H3>Výhody přímé integrace</h3>
<ul>
<li>Šetří síťový přenos</li>
<li>Odpadá nutnost budování REST API</li>
<li>Integrovaná služba je přímo dostupná v editoru procesů</li>
<li>Umožňuje budovat rozšíření Appstore</li>
</ul>
```
Pro nastudování problematiky přímé integrace s PIPES doporučujeme přečíst tyto kapitoly dokumentace:
- [Vlastní rozšíření PIPES](/docs/cs/extention)
- [PIPES SDK](/docs/cs/sdk)
- [Editor procesu](docs/cs/admin/process-editor)

[Jak na to vám nejlépe ukáží naše návody](/docs/cs/tutorials).


## Integrace prostřednictvím konektorů
Většina služeb v dnešní době komunikuje s okolím prostřednictvím REST API nebo SOAP. Některé služby informují o svých událostech prostřednictvím webhooks. Pro tyto způsoby komunikace využívají PIPES takzv. konektory. Pokud chceme komunikovat se službami prostřednictvím konektorů, můžem využít konektory připravené v [PIPES Appstore](/docs/cs/pipes-appstore), nebo si můžeme postavit konektory vlastní. 

![](/uploads/src_architecture/undirect_integration.png)

### Aplikace a konektory
Pro úplné pochopení architektury integrací v PIPES si nyní musíme vysvětlit pojmy **aplikace** a **konektor**. Začneme nejprve základním prvkem a tím je konektor.

**Konektor** je jednodušeně řečeno script, který komunikuje s rozhraním (nejčastěji REST API nebo SOAP) integrované aplikace. SDK framework poskytuje nástroje pro podporu této komunikace, jako je logování, ukládání metrik nebo vyhodnocení odpovědi. Konektor můžeme zapojit do orchestrovaného procesu, aby plnil funkci komunikace s požadovanou službou. Je znovupoužitelný a řeší vždy jeden konkrétní endpoint ingegrované služby, např. GET users. 

**Aplikace** má dvě hlavní funkce. Zajišťuje autentizaci komunikace (dnes nejčastěji OAuth 2) a nastavuje všechny atributy komunikace společné pro endpointy integrované služby. Kromě toho vytváří formulář pro zadávání přihlašovacích údajů a libovolných dalších uživatelských parametrů. Konektor následně využívá aplikaci k sestavení požadavku.

![](/uploads/src_architecture/app_and_connectors.png)

[Více na téma aplikací se dočtete v článku o Appstore](docs/cs/pipes-appstore). \
Pokud se chcete naučit pracovat s aplikacemi a konektory, doporučujeme naše návody: 
- [Jak vytvořit konektor pro volání REST API](/docs/cs/tutorials/basic-connector)
- [Jak vytvořit aplikaci s basic autentizací](/docs/cs/tutorials/basic-application)
- [Jak vytvořit aplikaci s autorizací OAuth 2](/docs/cs/tutorials/oauth2-application)



## Synchronní a asynchronní volání
TODO

## Metody pro synchronní volání
TODO