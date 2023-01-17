import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Pagination

This tutorial will show us how to deal with pagination of source data. In this case, Orchesty repeats queries to the remote system until it downloads the last page. It knows this by checking the number of items in the retrieved array. If that field is empty or the number of items is less than the page size, we terminate the iteration.

## Application

To demonstrate pagination, we will use the **GitHub** application that we have already created in the [Basic Application](../tutorials/basic-application.md) tutorial. We will create a connector to download the org repositories.

## Connector with cursor

To ensure that the queries and downloads of each page are repeated, we use the cursor. We increment it until we reach the last page. We identify the last page by a smaller or empty array of retrieved data. For the first iteration, when the cursor is not yet set, we pass a default value to the `getBatchCursor` method. The entire connector code then looks like this:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import GitHubApplication from "./GitHubApplication";

export const NAME = 'git-hub-repositories-batch';
const PAGE_ITEMS = 100;

export default class GitHubRepositoriesBatch extends ABatchNode {
    public getName(): string {
        return NAME;
    }

    public async processAction(dto: BatchProcessDto<IInput>): Promise<BatchProcessDto> {
        const page = dto.getBatchCursor('1');
        const { org } = dto.getJsonData();
        const appInstall = await this.getApplicationInstallFromProcess(dto);
        const request = await this.getApplication<GitHubApplication>().getRequestDto(
            dto,
            appInstall,
            HttpMethods.GET,
            `/orgs/${org}/repos?per_page=${PAGE_ITEMS}&page=${page}`,
        );
        const resp = await this.getSender().send<unknown[]>(request, [200]);
        const response = resp.getJsonBody();

        dto.setItemList(response ?? []);
        if (response.length >= PAGE_ITEMS) {
            dto.setBatchCursor((Number(page) + 1).toString());
        }

        return dto;
    }
}

export interface IInput {
    org: string;
}

```
</TabItem>

<TabItem value="php" label="PHP">

```php
namespace Pipes\PhpSdk\Batch;

use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;

final class GitHubGetRepositoriesBatch extends BatchAbstract
{

    public const NAME = 'git-hub-repositories-batch';

    private const PAGE_ITEMS = 5;

    function getName(): string
    {
        return self::NAME;
    }

    function processAction(BatchProcessDto $dto): BatchProcessDto
    {
        $currentPage = intval($dto->getBatchCursor('1'));
        $org         = $dto->getJsonData()['org'] ?? '';
        $appInstall  = $this->getApplicationInstallFromProcess($dto);

        $request = $this->getApplication()->getRequestDto(
            $dto,
            $appInstall,
            CurlManager::METHOD_GET,
            sprintf('/orgs/%s/repos?per_page=%s&page=%s', $org, self::PAGE_ITEMS, $currentPage),
        );
        $result  = $this->getSender()->send($request)->getJsonBody();
        $dto->setItemList($result);
        if (count($result) >= self::PAGE_ITEMS) {
            $dto->setBatchCursor((string) ($currentPage + 1));
        }

        return $dto;
    }

}

```
</TabItem>
</Tabs>

## Connector registration

Do not forget to register the connector in the container:

<Tabs>
<TabItem value="typescript" label="Typescript">

Register the connector into the container in `index.ts`.

```typescript
// ...
import { initiateContainer, listen, container } from '@orchesty/nodejs-sdk';
import GitHubRepositoriesBatch from './GitHubRepositoriesBatch';
// ...

export default async function prepare(): Promise<void> {

  // ...
    const mongoDbClient = container.get(CoreServices.MONGO);
    const curlSender = container.get(CoreServices.CURL);
    const gitHubApplication = new GitHubApplication();
    const gitHubRepositoriesBatch = new GitHubRepositoriesBatch();

    gitHubRepositoriesBatch
        .setSender(curlSender)
        .setDb(mongoDbClient)
        .setApplication(gitHubApplication);

    container.setBatch(gitHubRepositoriesBatch);
  // ...
}
```
</TabItem>
<TabItem value="php" label="PHP">

Register the batch connector in the yaml file: `./config/batch/batch.yaml`.

```php

# ./config/batch.yaml
services:
    _defaults:
        public: '%public.services%'

    hbpf.batch.git-hub-repositories-batch:
        class: Pipes\PhpSdk\Batch\GitHubGetRepositoriesBatch
        calls:
            - ['setApplication', ['@hbpf.application.git-hub']]
            - ['setSender', ['@hbpf.transport.curl_manager']]
            - ['setDb', ['@doctrine_mongodb.odm.default_document_manager']]
    // ...

```
</TabItem>
</Tabs>

## Test

The test is performed as in the previous tutorials. After the **start event** we include our new connector and have the output sent to the **user task**. 

![Pagination topology](/img/tutorial/pagination-topology.png "Pagination topology")

The connector expects the organization name in the data. When the process starts, we need to insert it:

![Pagination topology](/img/tutorial/batch/pagination-topology.svg "Pagination topology")

Our topology downloaded one page of input data, which it split into individual messages and sent to the user task. If we want to try pagination, we need to modify the `PAGE_ITEMS` connector variable to make its value less than the number of repositories in the organization.

The result should be the same. Only more queries have been made to retrieve the data.

## Cursoring without output

Previous example of cursoring was generating new messages with each iteration. In some cases it is better not to send processed data through queues, but to use data storage to store the collection we are working with.

If we don't want to send data to the followers in the topology with each iteration, we use the second parameter in the setBatchCursor method.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
setBatchCursor(cursor: string, iterateOnly = true)
```
</TabItem>
<TabItem value="php" label="PHP">

```php
setBatchCursor(string $cursor, bool $iterateOnly = FALSE)
```
</TabItem>
</Tabs>

When you set `iterateOnly = true`, no message will be sent to following nodes.
Orchesty will only repeater this action as describe above. 

:::tip
Using data storage is the most appropriate way for migrations and ETL processes with large volumes of batch data, where only **event message** is passed through the topology to control the execution of individual actions over the collection of data in the storage. You can read more about this in the [**Stored data**](../tutorials/stored-data) tutorial.
:::
