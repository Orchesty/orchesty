---
layout: main.hbs
collection: documentation
name: Budujeme první proces
parent: Tutorials
level: 2
index: 5

lunr: true
tags: first process
---

V minulém díle jsme se naučili [jak zaregistrovat Sdk jako službu do PIPES](/docs/cs/tutorials/sdk-settings). Nyní si ukážeme jak vytvořit náš první process.

## Vytvoření procesu

Přejdeme do Pipes Admin [http://127.0.0.10/ui/](http://127.0.0.10/ui/). Klikneme na záložku **File -> New topology**. Vyplníme jméno a potvrdíme. 

![](/uploads/scr_first_process/1_first_process.png "Vytvoření prvního procesu")

Přepneme se na editaci procesu a na canvas přetáhneme **Start event** a **User Task**. Poté na canvasu vybereme **User Task**, v sidebaru zvolíme jako implementaci naše zaregistrované sdk (PHP-sdk). Jako jméno vybereme **debug**. Debug, je task, který je předinstalovaný spolu s pipes sdk.

![](/uploads/scr_first_process/2_first_process_canvas.png "Vytvoření prvního procesu")

Process už stačí jen uložit **Save**, publikovat **... -> Publish** a enablovat **... -> Enable**.

![](/uploads/scr_first_process/3_newtopo_publish.png "Publikování prvního procesu")

## Otestování procesu

Přepneme se na kartu s metrikami a stiskneme **... -> Test**. PIPES provedou kontrolu, zda-li všechny uzly použité v procesu mají k dispozici patřičné kódy.
Po otestování naší topologie uvidíme u každého uzlu zelenou fajfku, to znamená, že uzel je připraven a správně nastaven.

![](/uploads/scr_first_process/4_newtopo_test.png "Otestování prvního procesu")

## Spuštění procesu

První uzel vypsaný v tabulce má typ **starting event**. Tento uzel se liší od ostatních tím, že má tlačítko pro ruční spuštění procesu.
Kliknutím na toto tlačítko získáme možnost vložit data, pokud proces na vstupu nějaké očekává. Protože náš proces žádná vstupní data neočekává, můžeme rovnou spustit testovací instanci.

![](/uploads/scr_first_process/5_newtopo_run.png "Spuštění prvního procesu")

## Výsledek procesu

Nyní přejdeme na záložku **Human Tasks**, kde můžeme vidět naše data, které jsme si poslali procesem.

![](/uploads/scr_first_process/6_newtopo_debug.png "Debugging procesu")


Gratulujeme nyní jste vytvořili a spustili svůj první PIPES proces. V další tutoriálu si ukážeme [jak vytvořit konektor pro REST API](/docs/cs/tutorials/basic-connector).