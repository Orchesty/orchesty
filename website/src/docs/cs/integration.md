---
layout: main.hbs
collection: documentation
name: Integrace
parent: Getting started
level: 2
index: 2
 
lunr: true
tags: integration integrace
lang: cs
---
 
PIPES nabízí dva základní principy integrace služeb, které se mohou vzájemně doplňovat a jejichž použití záleží na účelu služby v rámci infrastruktury a dalších okolnostech.
 
## Přímá integrace
Přímá integrace je základní integrační princip, který PIPES pro vývoj poskytují. Má mnoho výhod, ale není možné ji aplikovat ve všech případech. Při přímé integraci je třeba implementovat v integrované službě balíček PIPES SDK, který dodává framework a komunikaci s PIPES CORE službami. Tím je také dáno omezení tohoto způsobu. Není možné jej využít pro integraci služeb, do kterých nelze SDK implementovat.
 
![](/uploads/src_architecture/direct_integration.png)
 
Při přímé integraci se o veškerou komunikaci služeb s orchestrační vrstvou postarají samotné PIPES. Nám stačí využít framework SDK balíčku a zaregistrovat službu v aplikaci [PIPES Admin](/docs/cs/admin). Jak to udělat se dočtete v [tomto návodu](/docs/cs/sdk-settings).
 
 
``` infoBlock
<H3>Výhody přímé integrace</h3>
<ul>
<li>Šetří síťový přenos</li>
<li>Odpadá nutnost budování REST API</li>
<li>Integrovaná služba je přímo dostupná v editoru procesů</li>
<li>Umožňuje budovat rozšíření Appstore</li>
</ul>
```
Problematice přímé integrace s PIPES se věnujeme v těchto kapitolách dokumentace:
- [Vlastní rozšíření PIPES](/docs/cs/extention)
- [PIPES SDK](/docs/cs/sdk)
- [Editor procesu](docs/cs/admin/process-editor)
 
[Jak na to vám nejlépe ukáží naše návody](/docs/cs/tutorials).
 
 
## Integrace prostřednictvím konektorů
Většina služeb v dnešní době komunikuje s okolím pomocí REST API nebo SOAP. Některé služby informují o svých událostech prostřednictvím webhooků. Pro tyto způsoby komunikace využívají PIPES konektory. Pokud chceme komunikovat se službami prostřednictvím konektorů, můžeme využít konektory připravené v [PIPES Appstore](/docs/cs/pipes-appstore), nebo postavit vlastní.
 
![](/uploads/src_architecture/undirect_integration.png)
 
### Aplikace a konektory
Pro úplné pochopení architektury integrací v PIPES vysvětlíme nejprve pojmy **aplikace** a **konektor**. 
 
**Konektor** je třída (script), která komunikuje s rozhraním (nejčastěji REST API nebo SOAP) integrované služby. SDK framework poskytuje nástroje pro podporu této komunikace, jako je logování, ukládání metrik nebo vyhodnocení odpovědi. Konektor je opakovaně použitelný a řeší vždy jeden konkrétní endpoint integrované služby, např. “GET users”. Je možné jej využít samostatně, nebo s využitím **aplikace**.
 
**Aplikace** má dvě hlavní funkce. Zajišťuje autentizaci komunikace (dnes nejčastěji OAuth 2) a nastavuje všechny atributy komunikace společné pro endpointy integrované služby. Kromě toho vytváří formulář pro zadávání přihlašovacích údajů a libovolných dalších uživatelských parametrů. Konektor následně využívá aplikaci k sestavení požadavku.
 
![](/uploads/src_architecture/app_and_connectors.png)
 
[Více na téma aplikací se dočtete v článku o Appstore](docs/cs/pipes-appstore). \
Pokud se chcete naučit pracovat s aplikacemi a konektory, doporučujeme tyto návody:
- [Jak vytvořit konektor pro volání REST API](/docs/cs/tutorials/basic-connector)
- [Jak vytvořit aplikaci s basic autentizací](/docs/cs/tutorials/basic-application)
- [Jak vytvořit aplikaci s autorizací OAuth 2](/docs/cs/tutorials/oauth2-application)
 
 
 
## Synchronní a asynchronní volání
Většina integračních úloh řetězí sekvence více po sobě jdoucích akcí. Pro takové úlohy je ideální využít model architektury zvaný **Pipes and Filters**. Tato architektura představuje asynchronní procesy, ve kterých jsou jednotlivé akce propojené pomocí front.
 
![](/uploads/src_architecture/process.png)
 
Procesy vystavěné touto architekturou vhodně vystihují potřeby byznysového zadání a zároveň dokáží překonávat většinu výzev, se kterými je třeba se při integračních úlohách vypořádat. O řízení těchto procesů pomocí PIPES se dočtete více v kapitole [Orchestrace](/docs/cs/orchestration).
 
## Metody pro synchronní volání
Integrační vrstva PIPES umožňuje využívat konektory i pro synchronní volání bez využití orchestrační vrstvy. Aplikace integrační vrstvy mohou obsahovat metody, které využijí stejný konektor, jako se používá při asynchronních procesech s orchestrační vrstvou. Tyto metody předávají data jako HTTP response.
 
![](/uploads/src_architecture/sync_api.png)
 
/ Více o synchronním volání v kapitole [Jak budovat API pro synchronní volání](/docs/cs/sync-api).
 
## API
Vytvářením asynchronních procesů a aplikací s metodami pro synchronní volání vznikají API endpointy integrační vrstvy. PIPES takto můžeme využít jako **Enterprise Service Bus** nebo **API Gateway**. Postarají se o autentizaci veškerých požadavků i transformace dat a jejich využití záleží pouze na potřebách projektu.
 
![](/uploads/src_architecture/pipes_api.png)
 
V následujícím článku se dozvíte více o možnostech [orchestrace služeb pomocí PIPES](/docs/cs/orchestration).