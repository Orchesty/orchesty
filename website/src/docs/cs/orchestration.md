---
layout: main.hbs
collection: documentation
name: Orchestrace
parent: Getting started
level: 2
index: 3
 
lunr: true
tags: orchestration orchestrace
lang: cs
---
 
Orchestrační vrstva PIPES umožňuje modelování a provoz datových toků s důrazem na výkon a bezpečnost dat, resp. vytváří a řídí integrační procesy nad aplikační vrstvou infrastruktury. Každý proces přitom znamená sekvenci akcí, které jsou mezi sebou propojeny frontami.
 
![](/uploads/src_architecture/layers.png)
 
## Manažer procesů
Grafické uživatelské rozhraní [PIPES Admin](/docs/cs/admin) slouží jako manažer procesů. Umožňuje procesy vytvářet, spravovat a sledovat jejich provoz. Přináší komplexní pohled na datové toky infrastruktury, zobrazuje logy a metriky běžících procesů. 
![](/uploads/scr_orchestration/1_manager.png)
Kompletní přehled uživatelského rozhraní naleznete v článku [PIPES Admin](/docs/cs/admin).
 
 
## Modelování procesu
Modelování procesu probíhá v [grafickém editoru](/docs/cs/admin/process-editor). Zápis procesu vychází z notace pro modelování byznys procesů BPMN 2.0 a je s ní kompatibilní. Topologii procesu je možné připravit i v jiném nástroji s podporou BPMN 2.0 a následně ji do grafického editoru PIPES importovat.
 
![](/uploads/scr_orchestration/2_editor.png)
 
V grafickém editoru vytváříme topologii procesu, jeho jednotlivým akcím následně přiřadíme výkonný kód. Ten může být obsažen ve službě, kterou jsme vytvořili a připojili k orchestrační vrstvě metodou přímé integrace. Může se jednat i o konektor na službu, se kterou chceme komunikovat prostřednictvím API, což bude případ většiny služeb typu SaaS. O způsobech integrací se dočtete v kapitole [Integrace](/docs/cs/integration).
 
### Doporučené odkazy pro téma modelování procesů:
- [Editor procesu](/docs/cs/admin/process-editor)
- [Jak nastavit vlastní službu s využitím SDK pro přímou integraci s PIPES](/docs/cs/tutorials/sdk-settings)
- [Budujeme první proces](/docs/cs/tutorials/first-process)
 
 
## Provoz a verzování procesu
Po vymodelování procesu jej můžeme publikovat, čímž vytvoříme samostatný Docker kontejner s řídicí službou. Každý proces má tedy vlastní řídicí službu, nezávislou na službách ostatních.
 
![](/uploads/src_architecture/management_services.png)
 
Při úpravách běžícího procesu se automaticky vytváří nová verze, která je publikována ve stavu **disable**. Při současném přepnutí více verzí procesu do stavu **enable** jsou nové zprávy automaticky směrovány pouze do nejnovější verze. Mezi verzemi lze tímto způsobem libovolně přepínat, aniž by se ztrácely nebo duplikovaly zprávy, které do nich směřují.
 
## Metriky
PIPES zaznamenávají metriky všech instancí procesu, velikosti front před každou akcí procesu, dobu trvání zpracování instance procesu v každé akci, CPU time, a délku odezvy požadavku na integrovanou službu. Nasbírané metriky pak mohou sloužit k nalezení úzkého hrdla a následné optimalizaci procesu.
 
![](/uploads/scr_orchestration/3_metrics.png)
 
## Logy
Konfigurací PIPES lze volit mezi logováním do ELK stack nebo MongoDb. Je možné ukládat logy více úrovní. Vyšší úrovně logů se používají převážně pro informace s byznysovou hodnotou a jsou zobrazovány v PIPES Admin. Pro zobrazení všech podrobných logů je nutné využít dalších nástrojů pro vizualizaci dat, například Kibany v případě použití ELK stack.
 
![](/uploads/scr_orchestration/5_logs.png)
 
Více informací o možnostech logování v [dokumentaci](/docs/cs/documentation/logs).
 
## Notifikace
PIPES umožňují nastavení notifikací na nejrůznější druhy událostí. Notifikace odesílá **Status Service**, která po ukončení každého procesu obdrží zprávu s jeho vyhodnocením. Příjemce jednotlivých notifikací lze konfigurovat v **PIPES Admin** rozhraní. Notifikace lze odesílat na požadovanou URL nebo e-mailové adresy.
 
![](/uploads/scr_orchestration/4_notification.png)
 
### Doporučené odkazy:
- [Dokumentace k notifikacím](/docs/cs/documentation/notifications)
- [Jak nastavit zasílání notifikací](/docs/cs/tutorials/notifications)