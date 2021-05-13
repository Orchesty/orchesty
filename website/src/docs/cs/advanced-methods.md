---
layout: main.hbs
collection: documentation
name: Pokročilé metody
parent: Getting started
level: 2 
index: 4

lunr: true
tags: advanced methods pokročilé metody
lang: cs
---

## Opakované volání při selhání komunikace
PIPES umožňují nastavit opakované volání v případě, že se komunikace s integrovanou službou nezdaří. Toto opakování zajišťuje služba **Repeater**. Repeater je možné nastavit v každém konektoru samostatně. Jeho nastavení by mělo vycházet především z návratových kódů konkrétní služby. V Repeateru lze nastavit, při kterých návratových hodnotách se má použít, jakou frekvencí má zkoušet opakovaně volat a kolikrát má volání opakovat. 

### Související odkazy:
- [Dokumentace Repeateru](/docs/cs/documentation/repeater)
- [Ukázka použití v našem návodu](/docs/cs/tutorials/basic-connector)

## Pořadí zpráv a paralelní zpracování
PIPES dokáží zpracovávat zprávy paralelně. Při paralelním zpracování ale negarantují zachování pořadí zpráv. Paralelní zpracování závisí na nastavení **prefetch** každé akce procesu. Hodnota prefetch vyjadřuje počet zpráv, které si najednou vyzvedá **Bridge** z fronty ke zpracování. Bridge je řídící služba, která zná topologii procesu a zajišťuje volání každé akce a logiku spojenou s provozem procesu. Pokud nastavíme hodnotu prefetch na 1, Bridge nebude zpracovávat zprávy paralelně a tím zajistí pořadí zpracovávaných zpráv. 

``` infoBlock
Nastavením prefetch na 1 se sníží rychlost odbavování fronty. Je tedy nutné zvážit u každého procesu, zda dáme přednost výkonu nebo zachování pořadí zpráv.
```
Nastavení prefetch se provádí v [editoru procesu](/docs/cs/admin/process-editor) přímo v nastavení každé akce.

![](/uploads/scr_orchestration/6_prefetch.png)


## Nastavení limitů komunikace vzdálené služby
Služby typu SaaS omezují povolené množství požadavků na své API. Tyto limity mívají nejrůznější pravidla. Některé služby omezují přístup na základě uživatelského účtu. Různé uživatelské plány přitom mívají odlišné limity. Některé služby omezují podle IP adres a některé tato omezení dokonce kombinují.

Pro řešení těchto omezení poskytují PIPES službu **Limiter**. Limiter chytře omezuje volání služby, aby nedošlo k omezení komunikace díky překročení povoleného limitu. Aby mohl takové restrikce dělat účinně, hlídá limity napříč všemi konektory a topologiemi. 

![](/uploads/src_architecture/limiter.png)

Limiter lze nastavovat téměř libovolně. Které zprávy zahrnuje do společného počítání limitu záleží na definici klíče, který Limiteru předáme. Může to být identifikátor integrované služby, čímž omezíme souhrně volání této konkrétní služby. V případě vzájemné integrace svou služeb SaaS žádoucí přidat do klíče ještě ID uživatele. V takovém případě Limiter reguluje komunikaci každého uživatele dané služby zvlášť, i když je součástí stejných integračních procesů.

Limiter také počítá se situací, kdy například uživatel chybně nastaví svůj limit a integrovaná služba odmítne požadavek s informací o překročení limitu. V této situaci Limiter nastaví počítadlo na dosažený limit a omezí další komunikaci podle nastavených pravidel. V některých situacích služba vrací i údaje o povolených limitech. Je jen na programátorovi, zda nastaví pravidla automaticky podle těchto údajů, pokud je konektor obdrží.

### Související odkazy
- [Dokumentace služby Limiter](/docs/cs/documentation/limiter)
- [Jak nastavit limity komunikace](/docs/cs/tutorials/limiter)