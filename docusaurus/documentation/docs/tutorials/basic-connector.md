import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Basic connector

Connector's job is mainly to communicate with another services. This tutorial shows
how to create a connector fetching data from a REST API. In case of successful request, connector
sends data to next node for further processing.

An important responsibility of the connector is to evaluate the response and handle error conditions. Orchesty offers a number of options for setting the behavior of call error conditions. All are described on the [Response results](../documentation/results-evaluation.md) page. Here we list the main ones:

- **Repeat** - to retry the same process again after specified time delay
- **Stop** - to stop processing with either success of failure state
- **Limit** - in case of 'Too many requests' return message into Limiter
- **Ignore** - force process to continue

### Prerequisites

- [Installed and running Orchesty](../get-started/installation).
- [Connected SDK](SDK-settings)

## Creating Connector

<Tabs>
<TabItem value="typescript" label="Typescript">

First we create a new class, which will for simplicity extends **AConnector**. This abstract contains prepared method for using **CurlSender** used for calling REST API.

```typescript
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'jsonplaceholder-get-users';

export default class GetUsersConnector extends AConnector {
    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        return dto;
    }
}

```
</TabItem>
<TabItem value="php" label="PHP">

First we create a new class, which will for simplicity extends **ConnectorAbstract**. This abstract contains prepared method for using **CurlSender** used for calling REST API.

```php
namespace Pipes\PhpSdk\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;

final class GetUsersConnector extends ConnectorAbstract
{

    public const NAME = 'jsonplaceholder-get-users';

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

:::tip
It's also a good idea to prefix names by category, which can be for example a name of 3rd party service it's interacting with: **jsonplaceholder-get-users**.
:::

Next we'll implement a process method calling JsonPlaceholder.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';

export default class GetUsersConnector extends AConnector {

    // ...
    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const request = new RequestDto(
            'https://jsonplaceholder.typicode.com/users',
            HttpMethods.GET,
            dto,
        );

        const response = await this.getSender().send(request);
        dto.setData(response.getBody());

        return dto;
    }

}
```
</TabItem>
<TabItem value="php" label="PHP">

```php
namespace Pipes\PhpSdk\Connector;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

final class GetUsersConnector extends ConnectorAbstract
{

    // ...
    function processAction(ProcessDto $dto): ProcessDto
    {
        $request = new RequestDto(
            new Uri('https://jsonplaceholder.typicode.com/users'),
            CurlManager::METHOD_GET,
            $dto,
        );

        $response = $this->getSender()->send($request);
        $dto->setData($response->getBody());

        return $dto;
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

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        // ...

        const response = await this.getSender().send(request);
        if (response.getResponseCode() >= 300) {
            throw new OnRepeatException(30, 5, response.getBody());
        }

        dto.setData(response.getBody());

        return dto;
    }

}
```
</TabItem>
<TabItem value="php" label="PHP">

```php
use Hanaboso\CommonsBundle\Exception\OnRepeatException;

final class GetUsersConnector extends ConnectorAbstract
{
    
    // ...
  
    function processAction(ProcessDto $dto): ProcessDto
    {
      // ...

      $response = $this->getSender()->send($request);
      if ($response->getStatusCode() >= 300) {
          throw new OnRepeatException($dto, $response->getBody(), $response->getStatusCode());
      }

      $dto->setData($response->getBody());

      return $dto;
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
import OnRepeatException from '@orchesty/nodejs-sdk/dist/lib/Exception/OnRepeatException';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'jsonplaceholder-get-users';

export default class GetUsersConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const request = new RequestDto(
            'https://jsonplaceholder.typicode.com/users',
            HttpMethods.GET,
            dto,
        );

        const response = await this.getSender().send(request);
        if (response.getResponseCode() >= 300) {
            throw new OnRepeatException(30, 5, response.getBody());
        }

        dto.setData(response.getBody());

        return dto;
    }

}

```
</TabItem>
<TabItem value="php" label="PHP">

```php
namespace Pipes\PhpSdk\Connector;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

final class GetUsersConnector extends ConnectorAbstract
{

    public const NAME = 'jsonplaceholder-get-users';

    function getName(): string
    {
        return self::NAME;
    }

    function processAction(ProcessDto $dto): ProcessDto
    {
        $request = new RequestDto(
            new Uri('https://jsonplaceholder.typicode.com/users'),
            CurlManager::METHOD_GET,
            $dto,
        );

        $response = $this->getSender()->send($request);
        $dto->setData($response->getBody());
        if ($response->getStatusCode() >= 300) {
            throw new OnRepeatException($dto, $response->getBody(), $response->getStatusCode());
        }

        return $dto;
    }

}


```
</TabItem>
</Tabs>

## Registering into SDK container

<Tabs>
<TabItem value="typescript" label="Typescript">
Last step is to register connector into container. This is done in index.ts file located in root of src directory.

```typescript    
// ...
import { container } from '@orchesty/nodejs-sdk';
import CoreServices from '@orchesty/nodejs-sdk/dist/lib/DIContainer/CoreServices';
import CurlSender from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/CurlSender';
import GetUsersConnector from './GetUsersConnector';
// ...

export default async function prepare(): Promise<void> {
    // ...
    const curlSender = container.get<CurlSender>(CoreServices.CURL);

    const getUsers = new GetUsersConnector()
        .setSender(curlSender);
    container.setConnector(getUsers);
    // ...
};
```
</TabItem>
<TabItem value="php" label="PHP">
Last step is to register connector into container. This is done in connector.yaml file located config directory.

```php
# ./config/connector.yaml
services:
    _defaults:
        public: '%public.services%'
    hbpf.connector.jsonplaceholder-get-users:
        class: Pipes\PhpSdk\Connector\GetUsersConnector
        calls:
            - ['setSender', ['@hbpf.transport.curl_manager']]
```
</TabItem>
</Tabs>

## Building topology

Now we can create a new topology in Admin to test the new connector. In the topology, we will use the connector and user task to view the downloaded data.

![New connector](/img/tutorial/basicConnector/basic-connector-topology.svg "Basic connector topology")

We publish and activate the new topology and run it with empty data. In the **User Tasks** tab, we should now see a report with the data we got from the remote service.




