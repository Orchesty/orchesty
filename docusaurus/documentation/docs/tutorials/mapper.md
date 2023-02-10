import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Custom Node

Custom Node's primary job is data transformation. This type of node is meant for processes
which do not send requests. Let's create a simple custom node for data transformation.

Same as Connector even Custom Node's responsibility is to handle an error cases,
which almost exclusively means stopping the process as there are not any request failures,
thus errors are handled in one of two ways: 

- Stop: to stop processing with either success of failure state
- Ignore: force process to continue

### Prerequisites

- [Installed and running Orchesty](../get-started/installation).
- [Connected SDK](SDK-settings).
- Recommended [Basic connector](basic-connector) which covers common functionality.

## Creating Custom Node

First create a new class, which extends ACommonNode.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export class CustomNode extends ACommonNode {

  public getName(): string {
    return 'custom-node';
  }

  public processAction(_dto: ProcessDto): Promise<ProcessDto> | ProcessDto {
    return _dto;
  }

}
```
</TabItem>
</Tabs>


Next we'll implement a process method parsing and transforming data.

Incoming data contains list of persons where each person object contains name
and assignment information. We'll transform data to categorize persons under assignments
while also merging names and renaming 'persons' field.

Example of input data:

<Tabs>
<TabItem value="json" label="JSON">

```json
{
    "key": "lab",
    "persons": [
        {
            "id": 1,
            "name": "John",
            "surname": "Doe",
            "assignment": "workplaceA"
        },
        {
            "id": 2,
            "name": "Marry",
            "surname": "Joel",
            "assignment": "workplaceB"
        },
        {
            "id": 3,
            "name": "Ellis",
            "surname": "Birch",
            "assignment": "workplaceA"
        }
    ]
}
```
</TabItem>
</Tabs>


and desired output:

<Tabs>
<TabItem value="json" label="JSON">

```json
{
    "key": "lab",
    "personage": {
        "workplaceA": [
            {
                "id": 1,
                "fullname": "John Doe"
            },
            {
                "id": 3,
                "fullname": "Ellis Birch"
            }
        ],
        "workplaceB": [
            {
                "id": 2,
                "fullname": "Marry Joel"
            }
        ]
    }
}
```
</TabItem>
</Tabs>


<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export class CustomNode extends ACommonNode {

  public getName(): string {
    return 'custom-node';
  }

  public processAction(_dto: ProcessDto): Promise<ProcessDto> | ProcessDto {
    const dto = _dto;
    // Specify what is an input
    const data = dto.jsonData as IInput;

    // Whole data transformation
    dto.jsonData = {
      key: data.key,
      personage: data.persons.reduce((acc: IPersonage, it) => {
        // Single person transformation
        const transformed = {
          id: it.id,
          fullname: `${it.name} ${it.surname}`,
        };

        if (!(it.assignment in acc)) {
          acc[it.assignment] = [transformed];
        } else {
          acc[it.assignment].push(transformed);
        }

        return acc;
      }, {}),
      // Specifying output type to avoid unexpected keys
    } as IOutput;

    return dto;
  }

}

interface IInput {
  key: string,
  persons: {
    id: number,
    name: string,
    surname: string,
    assignment: string,
  }[]
}

// Exporting output type for any following node
export interface IOutput {
  key: string,
  personage: IPersonage,
}

export type IPersonage = Record<string, {
  id: number,
  fullname: string,
}[]>
```
</TabItem>
</Tabs>


:::tip
Specify and export output types so any following node can import and use it as an input type.
:::

### Registering into SDK container

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import { container, initiateContainer } from '@orchesty/nodejs-sdk';
import CustomNode from './CustomNode';

export default function prepare(): void {
  initiateContainer();

  container.setCustomNode(new CustomNode());
}
```
</TabItem>
</Tabs>


## Building topology process

Log into Admin and create a [new topology](../admin/admin.md).

Open an editor. Now we must add two nodes onto canvas to make it work.
First drag Start event onto canvas, then add a Custom Node. The two of them must be connected,
so select Start event and connect it to Connector.

The last step is to set our NodeJs script into Custom Node's action.
In this case it's a name 'custom-node'. Select Custom Node in canvas
and in right toolbar under Name dropbox choose appropriate action (custom-node).
If it's not visible there, check that the SDK is correctly registered.

If you want to try this process, don't forget to add input data from example above.
