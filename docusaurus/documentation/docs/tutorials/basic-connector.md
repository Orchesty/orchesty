import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Basic connector

Connector's job is mainly to communicate with another services. This tutorial shows
how to create a connector fetching data from a REST API. In case of successful request, connector
sends data to next node for further processing.

Důležitou zodpovědností konektoru je vyhodnocení odpovědi a zpracování chybových stavů. Orchesty nabízí řadu možností pro nastavení chování při chybových stavech volání. Všechny jsou popsané na stránce [Response results](../documentation/results-evaluation.md). Zde uvedeme ty hlavní:

- **Repeat** - to retry the same process again after specified time delay
- **Stop** - to stop processing with either success of failure state
- **Limit** - in case of 'Too many requests' return message into Limiter
- **Ignore** - force process to continue

### Prerequisites

- [Installed and running Orchesty](../get-started/installation).
- [Connected SDK](SDK-settings)

## Creating Connector

First we create a new class, which will for simplicity extends **AConnector**. This abstract contains prepared method for using **CurlSender** used for calling REST API.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export default class GetUsersConnector extends AConnector {
    public getName = () => 'jsonplaceholder-get-users';

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        return dto;
    }
}

```
</TabItem>
</Tabs>

:::tip
It's also a good idea to prefix names by category, which can be for example a name of 3rd party service it's interacting with: **jsonplaceholder-get-users**.
:::

Next we'll implement a process method calling JsonPlaceholder.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import HttpMethods from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';

export default class GetUsersConnector extends AConnector {
    
    // ...

    public async processAction(_dto: ProcessDto): Promise<ProcessDto> {
        const dto = _dto;
        const request = new RequestDto(
            'https://jsonplaceholder.typicode.com/users',
            HttpMethods.GET,
            dto,
        );

        const response = await this._sender.send(request);
        dto.data = response.body;

        return dto;
    }

}
```
</TabItem>
</Tabs>


## Error handling
Last step is error case handling. In this example we'll check result code and if it's 300 or above,
we'll re-try request after 30 seconds up to 5 times.

**OnRepeatException** is the simplest way to set a **repeater**. Another way is via setting headers which will be discussed in later tutorials. 

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import OnRepeatException from '@orchesty/nodejs-sdk/dist/lib/Exception/OnRepeatException';

export default class GetUsersConnector extends AConnector {
    
    // ...
  
    public async processAction(_dto: ProcessDto): Promise<ProcessDto> {
      // ...

      const response = await this._sender.send(request);
      if (response.responseCode >= 300) {
        throw new OnRepeatException(30, 5, response.body);
      }

      dto.data = response.body;

      return dto;
    }

}
```
</TabItem>
</Tabs>

## Whole code of connector
<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import HttpMethods from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import OnRepeatException from '@orchesty/nodejs-sdk/dist/lib/Exception/OnRepeatException';

export default class GetUsersConnector extends AConnector {
    public getName = () => 'jsonplaceholder-get-users';

    public async processAction(_dto: ProcessDto): Promise<ProcessDto> {
        const dto = _dto;
        const request = new RequestDto(
            'https://jsonplaceholder.typicode.com/users',
            HttpMethods.GET,
            dto,
        );

        const response = await this._sender.send(request);
        if (response.responseCode >= 300) {
            throw new OnRepeatException(30, 5, response.body);
        }

        dto.data = response.body;

        return dto;
    }
}
```
</TabItem>
</Tabs>

## Registering into SDK container

Last step is to register connector into container. This is done in index.ts file located in root of src directory.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import { container } from '@orchesty/nodejs-sdk';
import CoreServices from '@orchesty/nodejs-sdk/dist/lib/DIContainer/CoreServices';
import GetUsersConnector from './GetUsersConnector';
// ...

const prepare = async (): Promise<void> => {
  // ...
  const curlSender = container.get(CoreServices.CURL);

  const getUsers = new GetUsersConnector()
    .setSender(curlSender);
  container.setConnector(getUsers);
  // ...
};
```
</TabItem>
</Tabs>

## Building topology

Nyní můžeme v Adminu vytvořit novou topologii, kde si nový konektor vyzkoušíme. V topologii použijeme konektor a user task, kde si budeme moc stažená data prohlédnout.

![New connector](/img/tutorial/basicConnector/basic-connector-topology.png "Basic connector topology")

Publikujeme a aktivujeme novou topologii a spustíme ji s prázdnými daty. V záložce **User Tasks** bychom nyní měli vidět zprávu s daty, které jsme získali ze vzdálené služby.




