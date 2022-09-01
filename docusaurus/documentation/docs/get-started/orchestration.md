import Image from '/src/components/ThemedImg';
import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Orchestration

Orchestrace integrovaných služeb je řešena orchestrační vrstvou. Tato vrstva nám poskytuje řadu nástrojů a možností, jak si ukážeme v našich [návodech](../tutorials/getting-started-with-tutorials.md) a v [dokumentaci](../documentation/overview). Souhrnně můžeme říct, že orchestrační vrstva řídí a spravuje procesy mezi integrovanými službami.

## Messaging in Orchesty

Orchestrační vrstva v orchesty vytváří fronty zpráv mezi jednotlivými uzly procesních topologií s využitím **RabbitMq**. K tomu přidává řadu high level funkcí a byznisovou diagnostiku. Umožňuje tak flexibilně budovat procesní topologie, které reflektují byznisové potřeby.

## High level features

Orchestrační vrstva za nás řeší řadu situací, které bychom jinak museli pro bezproblémový chod procesů ošetřit. Poradí si například s [nedostupností integrované služby](../documentation/repeater.md). Poskytuje možnost jednoduše konfigurovat řadu opatření, které lze začlenit v případě neúspěšného volání. Např. počet a frekvenci opakovaných pokusů, různé možnosti [vyhodnocení po vyčerpání pokusů](../documentation/results-evaluation.md) volání nebo různé možnosti [notifikací](../documentation/notifications.md).

Topologie lze konfigurovat na výkon nebo dodržení pořadí zpráv. Můžeme psát vlastní filtry a routování zpráv. Zachycené nevalidní zprávy můžeme v [koši](../documentation/trash) opravit a poslat zpět do procesu.


## Rate limiting

Velmi důležitým pomocníkem zejména při integracích cloudových služeb je [limiter](../documentation/limiter.md). Díky němu můžeme konfigurovat rate limiting odchozích zpráv, abychom nepřekročili povolený počet volání na vzdálenou službu. Orchesty dokáže hlídat limity volání konkrétního API napříč všemi konektory i topologiemi.

## Multitenant integrations

Limiter dokáže dokonce definovat skupiny, díky kterým lze v každém konektoru rozlišit i jednotlivé uživatelské účty volané služby a hlídat tak rate limiting neomezeného počtu uživatelů, využívajících shodné procesní topologie. Taková funkce je neocenitelná při budování multitenantních integrací.

:::tip
**Multitenantní integrace** jsou vhodné např. při integracích **SaaS**. Orchesty má pro multitenantní integrace rozšíření [**Applinth**](https://applinth.io), které umožní pokročilé možnosti správy i budování vlastního integračního marketplace pro zákazníky SaaS.
:::

Veškeré možnosti orchestrační vrsty se naučíme využívat díky našim [tutoriálům](../tutorials/getting-started-with-tutorials.md). 
