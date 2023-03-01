import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Stored data

Especially with larger data collections, it is advisable to use data storage and not send data through topology queues. We will demonstrate the procedure for working with a data collection in data storage by modifying the connector to download the organization's repositories from **GitHub**. We prepared the connector as part of the previous [data pagination tutorial](../tutorials/pagination). So we recommend going through that tutorial first.

## Connector modification for data storage usage

For our example, we used the `GitHubRepositoriesBatch` connector and renamed it to `GitHubStoreRepositoriesBatch`.

We first pass a `DataStorageManager` to the connector, which manages the data storage work. Then we insert the data into the collection using the `store` method. When storing, it is important to label the collection so that we can correctly identify it in the next steps of the process.

The first parameter is the collection ID. It is the only mandatory parameter for identifying a collection. We have used the process ID, but any string can be used. When the iterations are finished, we send this ID in a message to the followers in the topology. The second parameter of the `store` method is the data itself.

:::tip
The `store` method allows 2 more parameters to be used to define the collection more precisely (see [**Data Storage documentation**](../documentation/data-storage)). These are particularly useful when using [**extensions for multitenant environments**](https://orchesty.io/applinth), e.g. for SaaS integrations where applications can be used and installed by customers with their own authentication.
:::


<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import DataStorageManager from '@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager';
import { PROCESS_ID } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
// ...

export default class GitHubStoreRepositoriesBatch extends ABatchNode {

    public constructor(private readonly dataStorageManager: DataStorageManager) {
        super();
    }
    
    // ...
    
    public async processAction(dto: BatchProcessDto<IInput>): Promise<BatchProcessDto> {
        
        // ...
        
        const resp = await this.getSender().send<IResponse>(request, [200]);
        const response = resp.getJsonBody();
        
        const processId = dto.getHeader(PROCESS_ID) ?? '';
        await this.dataStorageManager.store(
            processId,
            response
        );

        // ...
        
    }

}

type IResponse = IRepository[];

interface IRepository {
    repository: string;
}

// ...

```
</TabItem>
<TabItem value="php" label="PHP">

```php
use Hanaboso\PipesPhpSdk\Storage\DataStorage\DataStorageManager;
// ...

final class GitHubStoreRepositoriesBatch extends BatchAbstract
{

    public function __construct(
        ApplicationInstallRepository $repository,
        private readonly DataStorageManager $dataStorageManager,
    )
    {
        parent::__construct($repository);
    }

    function processAction(BatchProcessDto $dto): BatchProcessDto
    {
        // ...
        
        $result  = $this->getSender()->send($request)->getJsonBody();

        $processId = $dto->getHeader(PipesHeaders::PROCESS_ID);
        $this->dataStorageManager->store($processId, [Json::encode($result[0])]);
        
        // ...
    }

}

```
</TabItem>
</Tabs>

## Pagination and sending of the control message
In the next step, we set up pagination, or after the last iteration, we send a message with the ID of our collection.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...

export const NAME = 'git-hub-store-repositories-batch';
const PAGE_ITEMS = 5;

export default class GitHubStoreRepositoriesBatch extends ABatchNode {

    // ...

    public async processAction(dto: BatchProcessDto<IInput>): Promise<BatchProcessDto> {
        
        // ...

        if (response.length >= PAGE_ITEMS) {
            dto.setBatchCursor((Number(page) + 1).toString(), true);
        } else {
            dto.addItem({ processId });
        }

        // ...
    }

}

```
</TabItem>
<TabItem value="php" label="PHP">

```php
// ...
final class GitHubStoreRepositoriesBatch extends BatchAbstract
{
    private const PAGE_ITEMS = 5;
    
    // ...

    function processAction(BatchProcessDto $dto): BatchProcessDto
    {
        // ...
        
        if (count($result) >= self::PAGE_ITEMS) {
            $dto->setBatchCursor((string) ($currentPage + 1));
        } else {
            $dto->addItem($processId);
        }
        
        // ...
    }

}

```
</TabItem>
</Tabs>


We can see the full connector code here:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import DataStorageManager from '@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { PROCESS_ID } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';

export const NAME = 'git-hub-store-repositories-batch';
const PAGE_ITEMS = 5;

export default class GitHubStoreRepositoriesBatch extends ABatchNode {

    public constructor(private readonly dataStorageManager: DataStorageManager) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: BatchProcessDto<IInput>): Promise<BatchProcessDto> {
        const page = dto.getBatchCursor('1');
        const { org } = dto.getJsonData();
        const appInstall = await this.getApplicationInstallFromProcess(dto);
        const req = await this.getApplication().getRequestDto(
          dto,
          appInstall,
          HttpMethods.GET,
          `/orgs/${org}/repos?per_page=${PAGE_ITEMS}&page=${page}`,
        );
        const resp = await this.getSender().send<IResponse>(req, [200]);
        const response = resp.getJsonBody();
    
        const processId = dto.getHeader(PROCESS_ID) ?? '';
        await this.dataStorageManager.store(processId, response);

      if (response.length >= PAGE_ITEMS) {
          dto.setBatchCursor((Number(page) + 1).toString(), true);
      } else {
          dto.addItem({ processId });
      }

      return dto;
    }

}

type IResponse = IRepository[];

interface IRepository {
    repository: string;
}

export interface IInput {
    org: string;
}

```
</TabItem>
<TabItem value="php" label="PHP">

```php
namespace Pipes\PhpSdk\Batch;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\PipesPhpSdk\Storage\DataStorage\DataStorageManager;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;

final class GitHubStoreRepositoriesBatch extends BatchAbstract
{

    public const NAME = 'git-hub-store-repositories-batch';

    private const PAGE_ITEMS = 5;

    public function __construct(
        ApplicationInstallRepository $repository,
        private readonly DataStorageManager $dataStorageManager,
    )
    {
        parent::__construct($repository);
    }

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

        $processId = $dto->getHeader(PipesHeaders::PROCESS_ID);
        $this->dataStorageManager->store($processId, [Json::encode($result[0])]);

        if (count($result) >= self::PAGE_ITEMS) {
            $dto->setBatchCursor((string) ($currentPage + 1));
        } else {
            $dto->addItem($processId);
        }

        return $dto;
    }

}

```
</TabItem>
</Tabs>

## Connector registration

<Tabs>
<TabItem value="typescript" label="Typescript">

Register the connector into the container in `index.ts`.

```typescript
// ...
import { container } from '@orchesty/nodejs-sdk';
import CurlSender from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/CurlSender';
import DbClient from '@orchesty/nodejs-sdk/dist/lib/Storage/Database/Client';
import DataStorageManager from '@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager';
import FileSystemClient from '@orchesty/nodejs-sdk/dist/lib/Storage/File/FileSystem';
import GitHubStoreRepositoriesBatch from './GitHubStoreRepositoriesBatch';
import GitHubApplication from './GitHubApplication';
// ...

export default function prepare(): void {

    // ...
    const fileSystemClient = new FileSystemClient();
    const curlSender = container.get(CurlSender);
    const databaseClient = container.get(DbClient);
    
    const gitHubApplication = new GitHubApplication();
    
    const dataStorageManager = new DataStorageManager(fileSystemClient);
    container.set(dataStorageManager);
    
    const gitHubStoreRepositoriesBatch = new GitHubStoreRepositoriesBatch(dataStorageManager)
        .setSender(curlSender)
        .setDb(databaseClient)
        .setApplication(gitHubApplication);
    container.setBatch(gitHubStoreRepositoriesBatch);
    // ...
}
```
</TabItem>
<TabItem value="php" label="PHP">

Register the batch connector in the yaml file: `./config/batch/batch.yaml` and in `./config/services.yaml`.

```php

# ./config/services.yaml
services:
    _defaults:
        public: '%public.services%'

    hbpf.data_storage_manager:
        class: Hanaboso\PipesPhpSdk\Storage\DataStorage\DataStorageManager
        arguments:
            - '@hbpf.data_store.file_system'

# ./config/batch.yaml
services:
    // ...
    hbpf.batch.git-hub-store-repositories-batch:
        class: Pipes\PhpSdk\Batch\GitHubStoreRepositoriesBatch
        arguments:
            - '@hbpf.application_install.repository'
            - '@hbpf.data_storage_manager'
        calls:
            - [ 'setApplication', [ '@hbpf.application.git-hub' ] ]
            - [ 'setSender', [ '@hbpf.transport.curl_manager' ] ]
            - [ 'setDb', [ '@doctrine_mongodb.odm.default_document_manager' ] ]
                
    // ...

```
</TabItem>
</Tabs>

Thus we are ready to get the data collection and store it in the data storage.

## Getting data from data storage

To get the data from the stored collection, we create a simple custom node. We name the class e.g. `LoadRepositories`. Using this node, we will send the retrieved data to a follower so that we can view it in a user task that we include at the end of the topology. The code will look like this:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import DataStorageManager from "@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager";

export const NAME = 'load-repositories';

export default class LoadRepositories extends ACommonNode {

    public constructor(private readonly dataStorageManager: DataStorageManager) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { collection } = dto.getJsonData();
        const repos = await this.dataStorageManager.load(collection);
        return dto.setJsonData(repos);
    }

}

export interface IInput {
    collection: string;
}
```
</TabItem>
<TabItem value="php" label="PHP">

```php

namespace Pipes\PhpSdk\CommonNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;
use Hanaboso\PipesPhpSdk\Storage\DataStorage\DataStorageManager;

final class LoadRepositories extends CommonNodeAbstract
{

    public const NAME = 'load-repositories';

    public function __construct(
        ApplicationInstallRepository $repository,
        private readonly DataStorageManager $dataStorageManager,
    )
    {
        parent::__construct($repository);
    }

    function getName(): string
    {
        return self::NAME;
    }

    function processAction(ProcessDto $dto): ProcessDto
    {
        $data  = $dto->getJsonData();
        $repos = $this->dataStorageManager->load(id: $data['collection']);

        $res = [];
        foreach ($repos as $repo){
            $res[] = $repo->toArray();
        }

        return $dto->setJsonData($res);
    }

}

```
</TabItem>
</Tabs>

## Node registration


<Tabs>
<TabItem value="typescript" label="Typescript">

Register the node into the container in `index.ts`.

```typescript
// ...
import { container } from '@orchesty/nodejs-sdk';
import DataStorageManager from '@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager';
import FileSystemClient from '@orchesty/nodejs-sdk/dist/lib/Storage/File/FileSystem';
import LoadRepositories from "./LoadRepositories";
// ...

export default function prepare(): void {

    // ...
    const fileSystemClient = new FileSystemClient();

    const dataStorageManager = new DataStorageManager(fileSystemClient);
    container.set(dataStorageManager);

    container.setCustomNode(new LoadRepositories(dataStorageManager));
  // ...
}
```
</TabItem>
<TabItem value="php" label="PHP">

Register the node in the yaml file: `./config/custom_node/custom_node.yaml`.

```php

# ./config/custom_node.yaml
services:
    _defaults:
        public: '%public.services%'
        
    // ...

    hbpf.custom_node.load-repositories:
        class: Pipes\PhpSdk\CommonNode\LoadRepositories
        arguments:
            - '@hbpf.data_storage_manager'
            
```
</TabItem>
</Tabs>

## Test

Now we can build a topology to verify that our nodes are working correctly. We again include a user task after each node.

![Data store topology](/img/tutorial/data-store-topology.svg "Data store topology")

At startup, we need to pass the name of the organization to the connector.

![Topology run](/img/tutorial/start-data-store-modal.svg "Topology run")

Now we can check in the user tasks if the data runs through the topology as expected. While in the **user** node we should only see the ID of our collection, in **user2** we will see the complete data we downloaded from GitHub.

