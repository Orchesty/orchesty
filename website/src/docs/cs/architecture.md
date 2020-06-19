---
layout: main.hbs
collection: documentation
name: Architektura a development
level: 1 
index: 2
lang: cs
---

PIPES sami o sobě jsou mikroservisní platformou. Jednotlivé služby celého řešení běží v prostředí virtualizovaném prostřednictvím Docker kontejnerů. Celý systém je navržen tak, aby bylo možné ho pomocí dalších mikroservis rozšiřovat zejména o vlastní konektory, nebo služby pro transformace dat. Více o možnostech rozšíření se dozvíte v článku [Vlastní rozšíření PIPES](/docs/cs/extention), zde se budeme věnovat rozšíření pouze z hlediska architektury služeb a developmentu.

## Jak psát vlastní kód integračních procesů

Jakým způsobem lze tedy vytvořit službu, která bude sloužit jako rozšíření PIPES? Stačí vytvořit vlastní aplikaci, která implementuje jeden z balíčků [PIPES SDK](/docs/cs/sdk) a tuto službu zaregistrovat v [PIPES Admin](/docs/cs/admin) jako rozšíření. Tím zajistíme komunikaci mezi jádrem PIPES a připojenou službou. Tomuto způsobu připojení služby do PIPES říkáme [přímá integrace](/docs/cs/integration). 

![SDK Extensions](/uploads/src_architecture/sdk_extentions.png)

V aplikaci využívající přímou integraci s PIPES můžeme psát kód pro jednotlivé akce procesů. Ty se potom automaticky zobrazují v nabídce editoru procesů. Pro více informací na toto téma si přečtěte článek [Vlastní rozšíření PIPES](/docs/cs/extention).


``` infoBlock
Přímou integraci využíváme pro budování integrační vrstvy a rozšiřujících prvků PIPES, ale také v případě, kdy integrujeme služby v rámci servisní nebo mikroservisní infrastruktury a můžeme si dovolit v těchto službách implementovat PIPES SDK. Odpadá tím potřeba budovat v jednotlivých službách REST API. Oproti integraci s využitím REST API je zde také menší síťový přenos.
```

## Správa projektu a verzování

Budování projektu využívajícího orchestrační vrstvu PIPES probíhá stejně, jako stejně jako běžný vývoj. Kód  můžeme psát v IDE nástroji, na který jsme zvyklí. Programovací jazyk můžeme zvolit výběrem SDK balíčku. Díky mikroservisní architektuře můžeme dokonce využít více SDK a tím i různé programovací jazyky. Veškerý kód pak můžeme verzovat 



------------------------------------


Aplikaci postavenou na PIPES lze vyvýjet dvěma odlišnými způsoby. Pojďme si ukázat jak nelépe takovu aplikaci postavit.

## Monorepo
Monorepo je případ, kdy veškeré servisy máme v jednom repozitáři. Typicky jsou takové servisy umístěné do svých vlastních složek. 
I [Pipes-skeleton](https://github.com/hanaboso/pipes-skeleton) je ve skutečnosti monorepo, obsahující jednu servicu.

Monorepo použijeme například v situaci, kdy PIPES a jeho SDK budou pod správou jednoho týmu a apliakce jako celek nebude obsahovat desítky servis.
Tento způsob má své výhody stejně jako nevýhody.

### Ukázka práce s Monorepem
Jako základ použijeme naklonovaný repozitář Pipes-skeleton. Pokud budu potřebovat vytvořit další microservisu, pak jen přidám nový adresář na stejnou úroveň jako je php-sdk.
Tuto nově vzniklou servicu musíme následně přidat do ``docker-compose.yml``. 

``` PROJECT 1

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

### Výhody:
- Možnost spuštění celé infrastruktury z jednoho centrálního bodu (docker-compose.yml umístěný v rootu monorepa)

### Nevýhody
- Vyšší nároky na HW (CPU + RAM) při lokálním vývoji

## Více SingleRep
Tento způsob vývoje je naopak vhodný pro infrastruktury, které se zkládají z mnoha microservis. 
Pro vývoj je v rootu projektu vždy dostupný ``docker-compose.yml``, ve kterém je možné spustit PIPES, tak aby se dalo vyvýjet a testovat. Nicméně ostatní service (SDK) již chybí.

Infrastruktura se poté nasazuje tak, že existují jedny centrální PIPES, které poskytují  PIPES Admin a ostatní Core služby. Jednotlivá SingleRepa se pak deplojují samostatně a jen se pomocí PIPESAdmin zaregistrují jako SDK.

### Ukázka práce se SingleRepem
Jako základ použijeme naklonovaný repozitář Pipes-skeleton. Pokud budu potřebovat přidat novou servisu, pak vytvářím nový repozitář. Základem nově vzniklého repozitáře může, ale nemusí, být Pipes-skeleton.

``` PROJECT 2

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

### Výhody:
- Nižší nároky na HW (CPU + RAM) při lokálním vývoji

### Nevýhody
- Neožnost spuštění celé infrastruktury z jednoho centrálního bodu