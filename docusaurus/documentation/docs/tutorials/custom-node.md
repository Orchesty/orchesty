import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Custom Node

Custom node's primary job is data transformation. This type of node is meant for processes
which do not send requests. Let's create a simple custom node for data transformation.


### Prerequisites

- [Installed and running Orchesty](../get-started/installation).
- [Connected SDK](SDK-settings).

## Creating Custom Node

<Tabs>
<TabItem value="typescript" label="Typescript">

First, we create a new class in the worker in the **src** folder that will inherit from **ACommonNode**. The class doesn't do anything yet, we only set the name of the action that it will perform.

```typescript
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'hello-world';

export default class HelloWorld extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto): ProcessDto {
        return dto;
    }

}
```
</TabItem>
<TabItem value="php" label="PHP">


First, we create a new class in the worker in the **src** folder that will inherit from **CommonNodeAbstract**. The class doesn't do anything yet, we only set the name of the action that it will perform.

```php
namespace Pipes\PhpSdk\Mapper;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;

final class HelloWorld extends CommonNodeAbstract
{

    public const NAME = 'hello-world';

    function getName(): string
    {
        return self::NAME;
    }

    function processAction(ProcessDto $dto): ProcessDto
    {
        return $dto;
    }

}
```
</TabItem>
</Tabs>

:::info Note
Each process node must have a name identificator by which they are both registered and used within orchestration layer. By names, they are also listed in Admin, so it's best to keep them human-readable.
:::

## Data transformation

The **ProcessDto** object represents the data structure of the message that flows through the topology. Each node of the topology receives and resends data through this object. Thus, in the `processAction` method, we insert data into **ProcessDto**:


<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
    // ...
    public processAction(dto: ProcessDto): ProcessDto {
        return dto.setJsonData({ message: 'Hello world!' });
    }
    // ...
```
</TabItem>
<TabItem value="php" label="PHP">

```php
    // ...
    function processAction(ProcessDto $dto): ProcessDto
    {
        return $dto->setJsonData(['message' => 'Hello world']);
    }
    // ...
```
</TabItem>
</Tabs>



## Registering into SDK container

<Tabs>
<TabItem value="typescript" label="Typescript">

Now you need to register a new class in **SDK container**. This will make it available to the orchestration layer and we will be able to use it in topologies. We open the `index.ts` file in the `src` folder and register our `HelloWorld` class:

```typescript
// ...
import { container } from '@orchesty/nodejs-sdk';
import HelloWorld from './HelloWorld';
// ...

export default async function prepare(): Promise<void> {
    // ...
    container.setCustomNode(new HelloWorld());
    // ...
}
```
</TabItem>
<TabItem value="php" label="PHP">

Now you need to register a new class in **SDK container**. This will make it available to the orchestration layer and we will be able to use it in topologies. In the `config` folder, we create a `custom_node.yaml` file and register our `HelloWorld` class:

```php

# ./config/custom_node.yaml
services:
    _defaults:
        public: '%public.services%'
        
    hbpf.custom_node.hello-world:
        class: Pipes\PhpSdk\Mapper\HelloWorld
```
</TabItem>
</Tabs>

## Using in orchestration layer

Let's go back in Admin to our topology that we have already prepared. Select **custom node** in the toolbar and add it to the topology. In the right sidebar, we will now release the settings for the action that the node should perform. Here we have the worker we registered in the previous tutorial and also the **hello-world** action we created in it.

![Registration SDK](/img/tutorial/customNode/custom-node-setting.svg "Add custom node")

At the end of the topology, we add a user task to check the transformation.

![Registration SDK](/img/tutorial/customNode/hello-world-topology.svg "Hello world topology")

Now we save the topology. Since we have edited the published topology, we can see that a new version of it was automatically created, which will need to be published and enabled again in order to run it.

:::info
Orchesty always creates a new version when editing a published topology. When it is run, we then inactivate the previous active version. This stops receiving new messages, but all running process instances continue processing.
:::

So we start the topology, but this time we don't put any data into it. Switching to the **User Tasks** tab, we can see that there is a message with an empty body in the **user** node.

![Registration SDK](/img/tutorial/customNode/user-task-1.svg "User task 1")

We use the **Approve** button to send the message to the next node. The message should now appear in the **user2** node. When we look at the body of the message, we can see the data that our first custom node inserted.

![Registration SDK](/img/tutorial/customNode/user-task-2.svg "User task 2")

That's all. The principle of custom nodes is simple and its use is up to us. It can be used to prepare data mappings for integration processes. But using this principle, we can also orchestrate the microservice architecture and use the actions in custom nodes instead of the REST APIs of the individual microservices.

In the following tutorial, we will show how to create a connector to call the REST API of an external service.
