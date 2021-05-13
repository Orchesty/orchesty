---
layout: main.hbs
collection: documentation
name: Architektura a development
parent: Getting started
level: 2
index: 2
lang: cs
 
lunr: true
tags: architecture architektura
---
 
PIPES jsou mikroservisní platformou, jejíž jednotlivé služby běží ve virtualizovaném prostředí Docker kontejnerů. Celý systém je navržen tak, aby bylo možné ho rozšiřovat o vlastní konektory a služby pro transformace dat. Více o možnostech rozšíření v článku [Vlastní rozšíření PIPES](/docs/cs/extension). Zde se budeme věnovat rozšíření pouze z hlediska architektury služeb a developmentu.
 
## Jak psát vlastní kód integračních procesů
 
Veškerá rozšíření, převážně transformace dat a vlastní konektory, můžeme psát jako samostatné služby, které musí pouze implementovat jeden z balíčků [PIPES SDK](/docs/cs/sdk). Zaregistrováním služby v [PIPES Admin](/docs/cs/admin) zajistíme komunikaci mezi orchestrační vrstvou PIPES a připojenou službou. Tomuto způsobu připojení do orchestrační vrstvy říkáme [přímá integrace](/docs/cs/integration).
 
![SDK Extensions](/uploads/src_architecture/sdk_extentions.png)
 
V aplikaci využívající přímou integraci s PIPES můžeme psát kód pro jednotlivé akce procesů. Ty se potom automaticky zobrazují v nabídce editoru procesů, kde jsou k dispozici pro zařazení do procesní topologie. Více informací na toto téma v článku [Vlastní rozšíření PIPES](/docs/cs/extension).
 
 
``` infoBlock
Přímou integraci využíváme pro budování integrační vrstvy a rozšiřujících prvků PIPES, ale také v případě, kdy integrujeme služby v rámci servisní nebo mikroservisní infrastruktury a můžeme si dovolit v těchto službách implementovat PIPES SDK. Odpadá tím potřeba budovat v jednotlivých službách REST API. Oproti integraci s využitím REST API je výhodou menší síťový přenos.
```
 
## Správa a verzování projektu
 
Architektura PIPES umožňuje spravovat integrační vrstvu v běžném vývojářském prostředí. Nenutí tak vývojáře užívat jiné nástroje, než se kterými běžně pracují. Psaní kódu probíhá v libovolném IDE nástroji. Pro verzování používáme GIT repozitáře.
 
Při budování infrastruktury můžeme využívat **monorepo**(jeden repozitář pro všechny budované služby), nebo můžeme každou službu umístit v samostatném single repozitáři. 
 
### Monorepo
 
Základem pro oba přístupy je naklonovaný repozitář [Pipes-skeleton](https://github.com/hanaboso/pipes-skeleton). Jednotlivé služby zde můžeme přidávat do nových adresářů na úroveň adresáře ``php-sdk``. Pokud vytváříme PHP službu pro [přímou integraci](/docs/cs/integration), do nové složky zkopírujeme i výchozí obsah adresáře ``php-sdk``. 
 
``` PROJECT 2
 
my-app/
├── pipes-sdk/
│   ├── bin
│   ├── config
│   ├── ....
│   ├── Makefile
│   └── ...
└── my-new-service/
│   ├── bin
│   ├── ....
└── docker-compose.yml
```
 
Novou službu pak přidáme do  ``docker-compose.yml`` např. takto:
 
``` docker-compose.yml 1
 
services:
   my-new-service:
       image: my-new-service/image:tag
       user: ${DEV_UID}:${DEV_GID}
       working_dir: /var/www
       volumes:
           - ./my-new-service:/var/www:cached
 
```
 
Pokud nemáte zkušenosti s **docker compose**, doporučujeme nastudovat [původní dokumentaci](https://docs.docker.com/compose/).
 
 
``` infoBlock
<h4>Výhody:</h4>
Možnost spuštění celé infrastruktury z jednoho centrálního bodu (docker-compose.yml) umístěného v kořenovém adresáři repozitáře<br/>
<br/>
<h4>Nevýhody:</h4>
Vyšší nároky na HW (CPU + RAM) při lokálním vývoji
 
```
 
<br/>
 
### Samostatné single repozitáře
 
Ne vždy vyhovuje v projektu princip monorepo. Služby pro [přímou integraci](/docs/cs/integration) můžeme vytvářet v samostatných repozitářích a pracovat s nimi jako s kteroukoliv jinou aplikací. Jako základ můžeme využít opět [Pipes-skeleton](https://github.com/hanaboso/pipes-skeleton). Případně můžeme do vlastního repozitáře zkopírovat pouze obsah adresáře ``php-sdk``, kde je veškerý základ PHP projektu.
 
 
``` PROJECT 3
 
my-app/
├── pipes-sdk/
│   ├── bin
│   ├── config
│   ├── ....
│   ├── Makefile
│   └── ...
└── docker-compose.yml
 
my-app-2/
├── c-sharp-sdk/
│   ├── bin
|   └── ...
└── docker-compose.yml
```
 
 
``` infoBlock
<h4>Výhody:</h4>
Nižší nároky na HW (CPU + RAM) při lokálním vývoji.<br/>
<br/>
<h4>Nevýhody:</h4>
Infrastrukturu nelze spustit z jednoho centrálního bodu.
```
<br/>
 
### Symfony
 
Pokud máme vlastní **Symfony** projekt, stačí v composeru doplnit následující závislosti:
 
``` composer.json 4
 
"hanaboso/app-store": "^1.4",
"hanaboso/pipes-php-sdk": "^1.3",
```
V tomto případě je nutné zaregistrovat služby v config souborech. Konfiguraci můžete dohledat v adresáři [pipes-skeleton/php-sdk/config](https://github.com/hanaboso/pipes-skeleton/tree/master/php-sdk/config).
