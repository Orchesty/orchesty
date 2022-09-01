import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Custom Node

Custom node's primary job is data transformation. This type of node is meant for processes
which do not send requests. Let's create a simple custom node for data transformation.


### Prerequisites

- [Installed and running Orchesty](../get-started/installation).
- [Connected SDK](SDK-settings).

## Creating Custom Node

Nejprve v aplikaci, kterou jsme registrovali v Orchesty, vytvoříme ve složce **src** novou třídu, která bude dědit z **ACommonNode**. Třída zatím nic nedělá, pouze nastavíme jméno akce, kterou bude vykonávat.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';

export default class HelloWorld extends ACommonNode {
    public getName = (): string => 'hello-world';

    public async processAction(_dto: ProcessDto): Promise<ProcessDto> {
        return _dto;
    }
}
```
</TabItem>
<TabItem value="php" label="PHP">

```PHP

```
</TabItem>
</Tabs>

:::info Note
Each process node must have a name identificator by which they are both registered and used within orchestration layer. By names, they are also listed in Admin, so it's best to keep them human-readable.
:::

## Transformace dat

Objekt **ProcessDto** představuje datovou strukturu zprávy, která protéká topologií. Každý uzel topologie přijímá a opět odesílá data prostřednictvím tohoto objektu. V metodě **processAction** tedy vložíme do objektu data, takže metoda bude vypadat následovně:


<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
    //...
    public async processAction(_dto: ProcessDto): Promise<ProcessDto> {
        _dto.jsonData = {"message":"Hello world!"}
        return _dto;
    }
    //...
```
</TabItem>
<TabItem value="php" label="PHP">

```PHP

```
</TabItem>
</Tabs>



## Registering into SDK container
Nyní je potřeba registrovat novou třídu v **SDK kontejneru**. Tím se stane dostupnou pro orchestrační vrstvu a my ji budeme moc použít v topologiích. Otevřeme si soubor **index.ts** ve složce **src** a registrujeme naší třídu **HelloWorld**:


<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import { container } from '@orchesty/nodejs-sdk';
import CustomNode from './CustomNode';
// ...

export default async function prepare(): Promise<void> {
  // ...
  container.setCustomNode(new HelloWorld());
  // ...
}
```
</TabItem>
</Tabs>

## Použití v orchestrační vrstvě

Vrátíme se v adminu do naší topologie, kterou již máme připravenou. V panelu nástrojů vybereme **custom node** a přidáme ho do topologie. V pravém sidebaru nyní vydíme nastavení akce, kterou má daný uzel vykonávat. Zde se nám nabízí služba, kterou jsme v předchozím návodu registrovali a také akce **hello-world**, kterou jsme si v naší službě vytvořili.

![Registration SDK](/img/tutorial/customNode/custom-node-setting.png "Add custom node")

Nakonec topologie přidáme ještě user task, abychom si mohli tranformaci zkontrolovat.

![Registration SDK](/img/tutorial/customNode/hello-world-topology.png "Hello world topology")

Nyní topologii uložíme. Protože jsme upravovali publikovanou topologii, můžeme vidět, že se automaticky vytvořila její nová verze, kterou bude nutné opět publikovat a enablovat, abychom ji mohli spustit.

:::info
Orchesty při úpravách publikované topologie vždy vytváří novou verzi. Při jejím spuštění potom předchozí aktivní verzi pouze zneaktivníme. Tím přestane přijímat nové zprávy, ale všechny zprávy, které jsou v rámci topologie zpravovávané mohou doběhnout.
:::

Spustíme tedy topologii, ale tentokrát do ní nebudeme vkládat žádná data. Přepneme se do záložky **User Tasks** a můžeme vidět, že zde máme v uzlu **user** zprávu, která má prázdné tělo.

![Registration SDK](/img/tutorial/customNode/user-task-1.png "User task 1")

Tlačítkem **Approve** odešleme zprávu do dalšího uzlu. Zpráva by se nyní měla objevit v uzlu **user2**. Když se podíváme do těla zprávy, uvidíme v ní data, která vložil náš první custom node.

![Registration SDK](/img/tutorial/customNode/user-task-2.png "User task 2")

To je vše. Princip custom nodes je jednoduchý a jeho využití záleží jen na nás. Lze v něm připravovat mapování dat při integračních procesech. Pomocí tohoto principu lze ale také orchestrovat mikroservisní architekturu a akce v custom nodes využívat místo REST API jednotlivých mikroslužeb. 

V následujícím návodu si ukážeme, jak vytvořit konektor, kterým budeme volat REST API externí služby.