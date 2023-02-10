import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Introduction to Batch

In this tutorial, we will get an introduction to processing arrays of data. We can process data arrays in the same way as individual data objects, though it is often necessary to split the data from an array into individual elements and control their processing individually. A common case is pagination of the source data, which we will demonstrate in the next tutorial. For now, let's focus on data splitting.

### Prerequisites

- [Installed and running Orchesty](../get-started/installation).
- [Connected SDK](SDK-settings)
- Recommended [Basic connector](./basic-connector) which covers common functionality.

## Creating Batch

We split the data in a node of the `BatchNode` type. This is based on a connector. The difference is that it returns an array, and the orchestration layer then splits the array from this connector into individual messages. So we prepare a `SplitBatch` class, into which we directly insert an array of data. As we said, this class is essentially a connector, so it can get the data by calling some source. For our demonstration we will put the data array directly in the code.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';

export const NAME = 'split-batch';

export default class SplitBatch extends ABatchNode {
    public getName(): string {
        return NAME;
    }

    public processAction(dto: BatchProcessDto): Promise<BatchProcessDto> | BatchProcessDto {
        dto.setItemList([{ id: 1 }, { id: 2 }, { id: 3 }]);
        return dto;
    }
}

```
</TabItem>
<TabItem value="php" label="PHP">

```php
namespace Pipes\PhpSdk\Batch;

use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;

final class SplitBatch extends BatchAbstract
{

    public const NAME = 'split-batch';

    function getName(): string
    {
        return self::NAME;
    }

    function processAction(BatchProcessDto $dto): BatchProcessDto
    {
        $dto->setItemList([['id' => 1], ['id' => 2], ['id' => 3]]);
        return $dto;
    }

}

```
</TabItem>
</Tabs>

:::tip
We can try using the [basic connector](../tutorials/basic-connector.md) code in the `processAction` method. The difference is mainly in inserting data into `BatchProcessDto`. In our case, we need to use the `setItemList` method.
:::

Register Batch action into a container.

<Tabs>
<TabItem value="typescript" label="Typescript">

Register the batch connector in `index.ts` into the container.

```typescript
// ...
import { container } from '@orchesty/nodejs-sdk';
import SplitBatch from './SplitBatch';
// ...

export default async function prepare(): Promise<void> {
  // ...
  container.setBatch(new SplitBatch());
  // ...
};
```
</TabItem>
<TabItem value="php" label="PHP">

Register the batch connector in the yaml file: `./config/batch/batch.yaml`.

```php

# ./config/batch.yaml
services:
  // ...    
    hbpf.batch.split-batch:
        class: Pipes\PhpSdk\Batch\SplitBatch
  // ...

```
</TabItem>
</Tabs>

## Test

The test will be very simple this time. We'll create a topology where we include our new `split-batch` action after the start event, and finally add the user task again to see if our node has split the data as we expected.

![Split batch action](/img/tutorial/batch/split-batch.svg "Split batch action")

Start the process without entering data. The result should be 3 messages in the list on the **User Tasks** tab.

![Splitted messages](/img/tutorial/batch/splitted-messages.svg "Splitted messages")

In this tutorial, we've covered the basics of working with batches of data. We learned how to split an array into individual objects, which we will then process individually. We will use this, for example, when we retrieve source data by pagination.  This is because it is not always appropriate to process data in whole pages. In [the following tutorial](../tutorials/pagination) we will show how to do pagination when extracting data.

