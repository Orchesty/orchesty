import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Scheduled process

Plánované spouštění procesu je s Orchesty velice jednoduché a poskytuje stejné možnosti, jako plánování pomocí cronu.

## Vytvoření plánovaného procesu

Pravidelné spuštění procesu zajistíme použitím **cron event** při sestavování procesu. Můžeme ho vyzkoušet na libovolném procesu, která jsme si již v rámci předchozích návodů sestavili. V našem procesu použijeme náš první [custom node](../tutorials/custom-node.md) a vytvoříme proces, který bude každou minutu posílat data do user task. Na začátek procesu zařadíme prvek **cron**.

![Scheduled process](/img/tutorial/cron/cron-topology.png "Scheduled process")

Cron event nastavíme použitím zápisu ve formátu **cron tab**. Nepovinně můžeme zadat i vstupní data procesu. Nastavení provedeme v editoru v pravém sloupci nastavení uzlu. Do pole **Cron time** vložíme zápis `*/1 * * * *`.

![Cron settings](/img/tutorial/cron/cron-settings.png "Cron settings")

## Spuštění

Když nyní topologii uložíme a spustíme, v pravém horním rohu můžeme vidět čas příštího spuštění procesu. Pro jeho přerušení stačí proces deaktivovat tlačítkem **disable**.

![Next run](/img/tutorial/cron/next-run.png "Next run")

## Přehled plánovaných úloh

Přehled veškerých plánovaných úloh napříč všemi běžícími topologiemi naleznete v záložce **Scheduled tasks**.
