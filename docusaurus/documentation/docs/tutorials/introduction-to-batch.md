import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Introduction to Batch

V tomto návodu získáme úvod do zpracování polí dat. Pole dat můžeme samozřejmě zpracovávat stejným způsobem, jako jednotlivé datové objekty. Často je ale potřeba rozdělit data z pole na jednotlivé prvky a řídit jejich zpracování jednotlivě. Častým případem je stránkování zdrojových dat, které si ukážeme v příštím návodu. Teď se zaměříme na samotné rozdělení dat. 



### Prerequisites

- [Installed and running Orchesty](../get-started/installation).
- [Connected SDK](SDK-settings)
- Recommended [Basic connector](./basic-connector) which covers common functionality.

## Creating Batch

Rozdělení dat provádíme v uzlu typu `BatchNode`. Ten vychází v podstatě z konektoru. Rozdíl je v tom, že vrací pole a orchestrační vrstva pak pole z tohoto konketoru rozdělí na jednotlivé zprávy. Připravíme si tedy třídu `SplitBatch`, do které rovnou vložíme pole dat. Jak už jsme uvedli, tato třída je v podstatě konektorem, takže data může data získat zavoláním nějakého zdroje. Pro naší ukázku si ale vystačíme s tím, že pole dat vložíme rovnou v kódu.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';

export default class SplitBatch extends ABatchNode {
    public getName(): string {
        return 'split-batch';
    }

    public processAction(_dto: BatchProcessDto): Promise<BatchProcessDto> | BatchProcessDto {
        const dto = _dto;
        dto.setItemList([{ id: 1 }, { id: 2 }, { id: 3 }]);

        return dto;
    }
}

```
</TabItem>
</Tabs>

:::tip
Můžete si vyzkoušet použít v metodě `processAction` kód [basic konektoru](../tutorials/basic-connector.md). Rozdíl je především ve vložení dat do `BatchProcessDto`. Zde je nutné použít metodu `setItemList`.
:::

Register Batch action into a container.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import { container } from '@orchesty/nodejs-sdk';
import { SplitBatch } from './Tutorial/Batch/SplitBatch';
// ...

const prepare = async (): Promise<void> => {
  // ...
  container.setBatch(new SplitBatch());
  // ...
};
```
</TabItem>
</Tabs>

## Test

Test bude tentokrát velmi jednoduchý. Vytvoříme si topologii, kde za start event zařadíme naší novou akci `split-batch` a nakonec opět přidáme user task, abychom se mohli podívat, zda náš uzel rozdělil data tak, jak jsme očekávali.

![Split batch action](/img/tutorial/batch/split-batch.png "Split batch action")

Proces spustíme bez vkládání dat. Výsledkem by měly být 3 zprávy v seznamu v záložce **User Tasks**.

![Splitted messages](/img/tutorial/batch/splitted-messages.png "Splitted messages")

V tomto návodu jsme si ukázali základ pro práci s dávkami dat. Naučili jsme se rozdělit pole na jednotlivé objekty, které dál zpracujeme jednotlivě. To využijeme např. když získáváme zdrojová data stránkováním.  Ne vždy je totiž vhodné zpracovávat v procesu data po celých stránkách. V [následujícím návodu](../tutorials/pagination) si ukážeme, jak na samotné stránkování při získávání dat.

