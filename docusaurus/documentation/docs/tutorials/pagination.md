import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Pagination

Tento návod nám ukáže, jak řešit stránkování zdrojových dat. Orchesty v takovém případě opakuje dotazy na vzdálený systém, dokud nestáhne poslední stránku. Tu pozná kontrolou počtu položek v získaném poli. Pokud je to pole prázdné, nebo je počet položek menší než velikost stránky, iterace se ukončí.

## Příprava konektoru

Pro ukázku stránkování využijeme aplikaci **GitHub**, kterou jsme již vytvořili v rámci návodu [Basic Application](../tutorials/basic-application.md). Vytvoříme konektor pro stažení repozitářů orgarnizace. 

## Cursoring

Abychom zajistili opakování dotazů a stahování jednotlivých stránek, použijeme kurzor. Ten inkrementujeme vždy, dokud nedosáhneme poslední stránky. Tu poznáme podle menšího nebo prázdného pole. Metodě `getBatchCursor` předáme výchozí hodnotu pro první iteraci, kdy ještě není kurzor nastaven. Celý kód konektoru pak vypadá následovně:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';

export const NAME = 'git-hub-repositories-batch';
const PAGE_ITEMS = 100;

export default class GitHubRepositoriesBatch extends ABatchNode {
    public getName(): string {
        return NAME;
    }

    public async processAction(dto: BatchProcessDto): Promise<BatchProcessDto> {
        const page = dto.getBatchCursor('1');
        const { org } = dto.jsonData as {org: string};
        const appInstall = await this._getApplicationInstall();
        const req = await this._application.getRequestDto(
            dto,
            appInstall,
            HttpMethods.GET,
            `/orgs/${org}/repos?per_page=${PAGE_ITEMS}&page=${page}`,
        );
        const resp = await this._sender.send(req, [200]);
        const response = resp.jsonBody as unknown[];

        dto.setItemList(response ?? []);
        if (response.length >= PAGE_ITEMS) {
            dto.setBatchCursor((Number(page) + 1).toString());
        }

        return dto;
    }
}


```
</TabItem>

<TabItem value="php" label="PHP">

```php
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

    private const PER_PAGE = 5;

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
            sprintf('/orgs/%s/repos?per_page=%s&page=%s', $org, self::PER_PAGE, $currentPage),
        );
        $result  = $this->getSender()->send($request)->getJsonBody();
        $dto->setItemList($result);
        if (count($result) >= self::PER_PAGE) {
            $dto->setBatchCursor((string) ($currentPage + 1));
        }

        return $dto;
    }

}

```
</TabItem>
</Tabs>

## Registrace konektoru

Konektor nezapomeneme registrovat do kontejneru:

<Tabs>
<TabItem value="typescript" label="Typescript">

Konektor v `index.ts` zaregistrujeme do kontejneru .

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

Batch konektor registrujeme do yaml souboru: "./config/batch/batch.yaml"

```yaml
# ./config/batch/batch.yaml
services:
    _defaults:
        autowire: false
        autoconfigure: false
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

Test provedeme jednoduše stejně jako v předchozích návodech. Za **start event** zařadíme náš nový konektor a výstup si necháme poslat do **user task**. Konektor očekává v datech název organizace. Při spuštění procesu je musíme vložit:

![Pagination topology](/img/tutorial/batch/pagination-topology.png "Pagination topology")

Naše topologie stáhla jednu stránku vstupních dat, která rozdělila do jednotlivých zpráv a poslala do user task. Pokud chceme vyzkoušet stránkování, musíme upravit proměnnou konektoru `PAGE_ITEMS`, aby byla její hodnota menší, než počet repozitářů organizace.

Výsledek by měl být stejný. Pouze bylo provedeno víc dotazů pro získání dat.



## Cursoring without output

Previous example of cursoring was generating new messages with each iteration.
If you want to for example store fetched data into database and not process send then any further
use a second parameter in setBatchCursor method.

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
Toto je nejvhodnější způsob pro migrace a ETL procesy s velkými objemi dat v jedné dávce, kdy topologií prochází pouze **event message**, kterou řídíme spouštění jednotlivých akcí nad kolekcí dat v úložišti. Více o tomto postupu se dočtete v návodu [Stored data](../tutorials/stored-data).
:::
