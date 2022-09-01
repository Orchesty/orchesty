import Image from '/src/components/ThemedImg';

# First process

Orchesty je nástroj pro datové integrace a orchestrace procesů. Jako první krok si ukážeme základní práci s orchestrační vrstvou a sestavíme si první jednoduchý proces.

### Prerequisites

- [Installed and running Orchesty](../get-started/installation)

## Vytvoření topologie procesu
Definici procesu nazýváme topologie. Novou topologii vytvoříme pomocí tlačítka **plus** v levé liště admina. Topologie můžeme organizovat ve složkách a kromě vytvoření nové topologie máme i možnost importovat topologii připravenou. To vše se nám bude v budoucnu hodit, ale zatím si vystačíme s jednoduchým vytvořením topologie v rootu projektu.

<Image path="/img/firstProcess/plus-menu.png" lightOnly />

Z nabídky akcí vybereme "New topology" a topologii pojmenujeme.

<Image path="/img/firstProcess/new-topology.png" lightOnly />

Nyní se nám otevřel detail topologie. Jeho prostředí si budeme popisovat průběžně. Nejprve si ale otevřeme editor a sestavíme si náš první proces. Editor otevřeme tlačítkem s ikonou tužky v akčním menu v pravém horním rohu obrazovky.

<Image path="/img/firstProcess/action-menu.png" lightOnly />

V levé části editoru vidíme panel nástrojů. Kromě nástrojů pro výběr prvků zde můžeme najít různé typy prvků. První (kulaté) jsou události. Ty používáme jako výchozí prvky topologií. Můžete zde najít 3 typy událostí - **timer**, **webhook** a **start**. My si vybereme **start** a přetáhneme ho na canvas.

<Image path="/img/firstProcess/editor-start.png" lightOnly />

:::info
**Start event** vytváří přístupový bod dané topologie. Poskytuje URL pro zaslání dat, která mají být procesem zpracována. URL se generuje při publikování topologie (viz níže). Poté naleznete URL start eventu v pravém sidebaru editoru při označení prvku.
:::
Další sekcí editoru nástrojů jsou **akce**. Pro naší topologii si vybereme **user task** a přetáhneme ho na canvas. Kliknutím na **start event** zobrazíme jeho nástroje. Pomocí šipek pak oba prvky topologie propojíme.

<Image path="/img/firstProcess/first-process-topology.png" lightOnly />

Tím jsme sestavili první ukázkovou topologii s využitím akce **user task**. 

:::info
**User task** se hodí pro ruční úpravy dat pomocí uživatelského rozhraní, ale také ho lze velice jednoduše použít pro krokování dat při sestavování topologií. Níže si ukážeme jak.
:::

## Publikování topologie

Nyní připravenou topologii uložíme pomocí tlačítka **Save** v akčním menu a tlačítkem **Back** zavřeme editor. Tím jsme uložili změny. Topologii máme ale stále ve stavu **Draft**. Abychom připravený proces spustili, musíme ho ještě publikovat pomocí tlačítka **Publish**.

<Image path="/img/firstProcess/action-menu.png" lightOnly />

:::info
Publikováním topologie vytvoří kontejner s řídící službou a fronty procesu. O vše se postará orchestrační vrstva.
:::

## Enable/disable procesu

Nově publikovaný proces je ve stavu neaktivní. To znamená, že nepříjímá žádné signály start eventu. Jakmile přepneme proces do stavu enable, začne přijímat požadavky na URL start eventu.

<Image path="/img/firstProcess/enable.png" lightOnly />

:::info
**Disable** topologie uzavírá její vstupní body. Topologie nepřijímá žádné signály a timery nespouští plánované procesy. Všechny probíhající instance procesů ale pokračují.
:::

## Ruční spuštění procesu

Pokud máme připravenou topologii, proces můžeme spustit i ručně tlačítkem **Run** v akčním menu. V okně, které se nám otevře, můžeme vybrat požadovaný start event, pokud jich má topologie víc. Do těla zprávy vložíme požadovaná data ve formátu JSON.

<Image path="/img/firstProcess/run-modal.png" lightOnly />

Spustíme proces a přesuneme se do záložky **User tasks** v detailu topologie. V této záložce můžeme vidět všechny zprávy všech user tasks topologie. Když klikneme na naší zprávu, v detailu můžeme vidět její hlavičky a data. Data je možné před odesláním upravit.


Pokud tedy vidíte vaší první zprávu v přehledu user tasks, úspěšně jste spustili svůj první proces. Tlačítkem **Approve** proces dokončíte. 

V další kapitole si ukážeme, jak přihlásit k orchestrační vrstvě službu, ve které můžeme tvořit naše scripty a konektory s využitím SDK.


