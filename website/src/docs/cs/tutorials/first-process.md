---
layout: main.hbs
collection: documentation
name: Budujeme první proces
parent: Tutoriály
level: 2
index: 5

lunr: true
tags: first process první proces
lang: cs
---

V minulém díle jsme se naučili, [jak zaregistrovat kontejner se službou pro přímou integraci s PIPES](/docs/cs/tutorials/sdk-settings). Nyní si ukážeme, jak vytvořit náš první process.

## Co budeme potřebovat?
- Nainstalované PIPES na svém localhostu pro vytvoření nového konektoru. Instalaci můžete provést pomocí návodu [Instalace a spuštění PIPES](/docs/cs/installation).
- Připravenou službu s implementovaným balíčkem SDK, registrovanou v PIPES pro přímou integraci. Pokud službu ještě nemáte, podívejte se na kapitolu [Jak použít vlastní službu s využitím SDK pro přímou integraci s PIPES](/docs/cs/tutorials/sdk-settings/).

## Vytvoření procesu

Procesy budujeme v prostředí [PIPES Admin](/docs/cs/admin), které je dostupné na adrese  [http://127.0.0.10/ui/](http://127.0.0.10/ui/). V horní liště klikneme na záložku **File -> New topology**, vyplníme jméno a potvrdíme. 

![](/uploads/scr_first_process/1_first_process.png "Vytvoření prvního procesu")

Přepneme se na editaci procesu a na canvas přetáhneme **Start event** a **User Task**. Poté na canvasu vybereme **User Task**, v sidebaru zvolíme jako implementaci naše zaregistrované sdk (PHP-sdk). Jako jméno zvolíme **debug**. Debug, je task, který je předinstalovaný spolu s pipes sdk.

![](/uploads/scr_first_process/2_first_process_canvas.png "Vytvoření prvního procesu")

Proces už stačí jen uložit **Save**, publikovat **... -> Publish** a enablovat **... -> Enable**.

![](/uploads/scr_first_process/3_newtopo_publish.png "Publikování prvního procesu")

## Otestování procesu

Přepneme se na kartu s metrikami a stiskneme **... -> Test**. PIPES provedou kontrolu, zdali všechny uzly použité v procesu mají k dispozici patřičné kódy.
Po otestování naší topologie uvidíme u každého uzlu zelenou fajfku. To znamená, že uzel je připraven a správně nastaven.

![](/uploads/scr_first_process/4_newtopo_test.png "Otestování prvního procesu")

## Spuštění procesu

První uzel vypsaný v tabulce má typ **starting event**. Tento uzel se liší od ostatních tím, že má tlačítko pro ruční spuštění procesu.
Kliknutím na toto tlačítko získáme možnost vložit data, pokud proces na vstupu nějaká očekává. Protože náš proces vstupní data neočekává, můžeme rovnou spustit testovací instanci.

![](/uploads/scr_first_process/5_newtopo_run.png "Spuštění prvního procesu")

## Výsledek procesu

Nyní přejdeme na záložku **User Tasks**, kde vidíme naše data, které jsme si poslali procesem.

![](/uploads/scr_first_process/6_newtopo_debug.png "Debugging procesu")


Gratulujeme, nyní jste vytvořili a spustili svůj první PIPES proces. V dalším tutoriálu si ukážeme, [jak vytvořit konektor pro REST API](/docs/cs/tutorials/basic-connector).