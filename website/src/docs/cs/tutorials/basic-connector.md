---
layout: main.hbs
collection: documentation
name: Jak vytvořit konektor pro volání REST API
parent: Tutorials
level: 2
index: 10

lunr: true
tags: basic connector
---


V tomto článku se naučíme vytvořit konektor, kterým získáme data ze služby s rozhraním REST API. Konektor je v podstatě základním prvkem integračního procesu a jeho úkolem je odeslání požadavku a vyhodnocení odpovědi. V případě úspěšného volání konektor předává získanou odpověď do procesní topologie, kde s nimi můžeme dál pracovat. Úlohou konektoru je ale i vyhodnocení chybových odpovědí a nastavení chování po získání chybového kódu. PIPES nabízí několik možných scénářů, jak chybový stav volání ošetřit:

- Opakované volání pomocí repeateru, kdy můžeme nastavit počet pokusů a interval mezi nimi.
- Ukončení instance procesu, tedy vyhodnocení procesu jako neúspěšného. Tato možnost se nabízí i jako scénář po posledním neúspěšném opakovaném volání. 
- Ignorování stavu, případně ošetření stavu v datech instance procesu.
- Nastavení limiteru, což je možnost, která se využívá při překročení limitů volání vzdálené služby. Limiteru lze nastavit maximální počet volání v určitém časovém úseku. Jeho využití popisuje samostatná kapitola.

## Co budete potřebovat?
- Pro vytvoření nového konektoru předpokládáme, že máte nainstalované PIPES na svém localhostu. Pokud ne, podívejte se na článek Instalace PIPES a spuštění ukázkového procesu. 
- Připravenou službu s implementovaným balíčkem SDK, registrovanou v PIPES pro přímou integraci. Pokud službu ještě nemáte, podívejte se na kapitolu Jak nastavit vlastní službu s využitím SDK pro přímou integraci s PIPES.

## Vytvoření konektoru
Vytvoříme konektor, který využije CURL transport service pro získání dat z REST API testovací služby. Nejprve vytvoříme třídu konektoru, která implementuje…..

``` PHP 1
CODE
```

Dál vytvoříme metodu, která nastaví volání CURL transport service. Pro náš první konektor využijeme službu JSONPlaceholder. Můžeme zvolit třeba data s výpisem uživatelů [https://jsonplaceholder.typicode.com/users](https://jsonplaceholder.typicode.com/users).

``` PHP 2
CODE
```


Následně vytvoříme metodu pro vyhodnocení response. V této metodě vyhodnotíme všechny možnosti, které mohou při volání služby nastat. V případě úspěšného volání předáme získaná data do objektu Payload???:

``` PHP 3
CODE
```

V případě nedostupné služby nastavíme Repeater. V tomto případě říkáme Repeatru, aby volání opakoval třikrát v intervalu 1 minuty:

``` PHP 4
CODE
```

Pro případ jiných chybových návratových kódů, nebo pokud selžou všechny pokusy Repeateru, vyhodnotíme proces jako ukončený s chybou. Tuto situaci popíšeme v logu:

``` PHP 5
CODE
```

Zbývá zaregistrovat třídu konektoru jako službu, kterou pojmenujeme „test_connector“. Tím máme konektor připravený k použití.

``` PHP 6
CODE
```
## Použití konektoru v integračním procesu
Přihlásíme se do uživatelského rozhraní a vytvoříme nový proces. V záložce „Soubor“ klikněte na odkaz „Vytvořit topoligii“…. TODO

V detailu topologie se přepneme do záložky editoru. Nyní vytvoříme jednoduchý proces a ukážeme si, jak snadno nový konektor v procesu použijeme. Vytvořte na canvasu Start Event. Ten je nutný pro volání procesu. Přetáhneme Start event z toolbaru na canvas.
V toolbaru vybereme  prvek „Connector“, vložíme ho na canvas a propojíme se Start eventem.

![](/img/test_connector.png "Test connector")

Nyní nastavíme pro novou akci script, který bude vykonávat. V našem případě se jedná o testovací konektor, který jsme pojmenovali „test_connector“. Klikneme tedy na prvek akce na canvasu a v pravém sidebaru klikneme na rozbalovací nabídku „Name“. Pokud jsme správně provedli všechny předchozí kroky, měli bychom náš konektor vidět v rozbalovací nabídce. Pokud konektor nevidíte, zkontrolujte, že máte správně zaregistrovanou službu s SDK balíčkem a v ní správně zaregistrovanou službu s novým konektorem. Vybereme tedy položku „test_connector“. 

![](/img/select_script.png "Select script")

Tím máte vytvořený první proces, který získá data z testovací služby.

JAK TEN TUTORIÁL UKONČIT???

TODO: Otestování konektoru a zobrazení získaných dat… (pomocí user task a debug node)

## Otestování procesu

Pro jednoduchý náhled na získaná data a otestování konektoru připojíme na konec topologie debug task, který nám umožní zobrazit data procesu v uživatelském rozhraní. 

![](/img/debug_task.png "Debug task")

Připravený proces nyní uložíme a publikujeme. Uděláme to pomocí rozbalovací nabídky v pravém horním rohu. Tím PIPES vygenerují Docker kontejner s řídící službou procesu a proces je připraven k otestování.

![TODO: obrázek s nabídkou publikování.](/img/placeholder_img.png "Uložení procesu")

Tlačítkem v horní liště procesu se přepneme do zobrazení metrik. Zde vidíme nejprve blok, zobrazující metriky procesu a následně bloky, zastupující jednotlivé uzly procesu. 

![TODO: obrázek s nabídkou publikování.](/img/placeholder_img.png "Uložení procesu")

První blok procesních uzlů zastupuje Start event. Liší se od ostatních mimo jiné tlačítkem pro spuštění instance procesu. Kliknutím na toto tlačítko získáme možnost vložit data, pokud proces na vstupu nějaké očekává. Protože náš proces žádná vstupní data neočekává, můžeme rovnou spustit testovací instanci.

![TODO: obrázek s ukázkou spuštění.](/img/placeholder_img.png "Spuštění procesu")

Nyní přejdeme v horní liště do záložky Human tasks, kde ve zobrazené tabulce uvidíme záznam našeho debugovacího uzlu. Po rozkliknutí detailu záznamu pak uvidíme data, která nám v rámci této instance procesu přišla.

![TODO: obrázek s detailem user tasku (debug tasku).](/img/placeholder_img.png "Spuštění procesu")

## Automatizovaný test

Na závěr ještě ukážeme, jakým způsobem můžete napsat pro vytvořený konektor automatizovaný test. Rozhodně doporučujeme automatizované testy psát, protože testování by mělo být základem každé programátorské práce.

TODO: Popsat Live test...

Doufáme, že vám tento návod pomohl. Naučili jsme se vytvořit jednoduchý konektor, který využijete, pokud se nemusíte napojovat na víc endpointů dané služby a pokud si vystačíte s Basic autorizací. V našem dalším návodu si ukážeme, jak vytvořit vlastní aplikaci, kterou lze následně využít pro psaní více konektorů jedné služby. [Klikněte zde, pokud se chcete naučit vytvářet vlastní aplikace.](/)