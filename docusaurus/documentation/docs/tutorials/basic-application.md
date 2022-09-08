import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';


# Basic Application

In previous tutorial we've create a simple connector without any authorization. Connector requiring authorizations are usually supported via Application class. 

**Aplikace** představuje  prostředek pro autorizaci volání. Poskytuje formulář pro nastavení přístupových údajů a libovolná další uživatelská nastavení. Nad to může obsahovat vše, co je společné jejím konektorům. 

In this tutorial we'll create a BasicAuthorization application which will prepare HTTP request filling up Authorization header. Also we'll create a simple UI form for authorization settings. This time we'll connect to **GitHub**. 

## Prerequisites

- [Installed and running Orchesty](../get-started/installation).
- [Connected SDK](SDK-settings)

## Creating application 

<Tabs>
<TabItem value="typescript" label="Typescript">

Nejprve vytvoříme ve složce **src** třídu aplikace, dědící z **ABasicApplication**.

```typescript
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { ABasicApplication } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';

export const NAME = 'git-hub';

export default class GitHubApplication extends ABasicApplication {

    public getName(): string {
        return NAME;
    }

    public getPublicName(): string {
        return 'Git Hub';
    }

    public getDescription(): string {
        return 'Git Hub application';
    }
}
```
</TabItem>
<TabItem value="php" label="PHP">

Nejprve vytvoříme ve složce **src** třídu aplikace, dědící z **BasicApplicationAbstract**.

```php
namespace Pipes\PhpSdk\Application;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;

final class GitHubApplication extends BasicApplicationAbstract
{

    public const NAME = 'git-hub';
    
    public function getName(): string
    {
        return self::NAME;
    }
    
    public function getPublicName(): string
    {
        return 'Git hub';
    }
    
    public function getDescription(): string
    {
        return 'Git Hub application';
    }
}
```
</TabItem>
</Tabs>

Metody v této části kódu slouží jsou povinné. **Name** souží jako jedinečný identifikátor aplikace. **PublicName** a **Description** se zobrazují v [Orchesty marketplace](../admin/marketplace.md).

## Form
Pro každou aplikaci můžeme vytvořit libovolný počet [formulářů](../documentation/form.md) pro uživatelská nastavení komunikace. V našem příkladu vytvoříme jeden formulář pro zadání přístupových údajů k účtu v HubSpot. Ve formuláři potřebujeme zadávat uživatelské jméno a token, který můžeme vygenerovat ve svém účtu v HubSpot.



<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import {
    ABasicApplication,
    TOKEN,
    USER,
} from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
// ...

// ...
export default class GitHubApplication extends ABasicApplication {

    // ...
    public getFormStack(): FormStack {
        const form = new Form(AUTHORIZATION_FORM, 'Authorization settings')
            .addField(new Field(FieldType.TEXT, USER, ' User name', undefined, true))
            .addField(new Field(FieldType.TEXT, TOKEN, ' Token', undefined, true));

        return new FormStack().addForm(form);
    }
    // ...

}
```
</TabItem>
<TabItem value="php" label="PHP">

```php
// ...
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
// ...

// ...
final class GitHubApplication extends BasicApplicationAbstract
{

  // ...
  public function getFormStack(): FormStack
  {
      $authForm = new Form(self::AUTHORIZATION_FORM, 'Authorization settings');
      $authForm
          ->addField(new Field(Field::TEXT, self::USER, 'Username', NULL, TRUE))
          ->addField(new Field(Field::TEXT, self::TOKEN, 'Token', NULL, TRUE));

      $stack = new FormStack();
      $stack->addForm($authForm);

      return $stack;
  }
  // ...

}
```
</TabItem>
</Tabs>

## Request

Next step is finishing method for setting up RequestDto for connectors. This method will fill Authorization header and returns fully built RequestDto object ready to be send. URL, method and body provides connector calling this Application. 

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import { encode } from '@orchesty/nodejs-sdk/dist/lib/Utils/Base64';
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
// ...

export default class GitHubApplication extends ABasicApplication {

    // ...
    public getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        url?: string,
        data?: unknown,
    ): RequestDto {
        const request = new RequestDto(`https://api.github.com${url}`, method, dto);
        const form = applicationInstall.getSettings()[AUTHORIZATION_FORM] ?? {};
        request.setHeaders({
            [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
            [CommonHeaders.ACCEPT]: JSON_TYPE,
            [CommonHeaders.AUTHORIZATION]: encode(`${form[USER] ?? ''}:${form[TOKEN] ?? ''}`),
        });

        if (data) {
            request.setJsonBody(data);
        }

        return request;
    }
    // ...

}
```
</TabItem>
<TabItem value="php" label="PHP">

```php
// ...
use GuzzleHttp\Psr7\Uri;
// ...

final class GitHubApplication extends BasicApplicationAbstract
{

    // ...
    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $form = $applicationInstall->getSettings()[self::AUTHORIZATION_FORM] ?? [];

        return new RequestDto(
            new Uri(sprintf('https://api.github.com%s', $url)),
            $method,
            $dto,
            $data ?? '',
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => base64_encode(
                    sprintf('%s:%s', $form[self::USER] ?? '', $form[self::TOKEN] ?? ''),
                ),
            ],
        );
    }
    // ...
    
}
```
</TabItem>
</Tabs>

## Celý kód aplikace
To je vše.  Níže si můžete prohlédnout celý kód aplikace.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import {
    ABasicApplication,
    TOKEN,
    USER,
} from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import { encode } from '@orchesty/nodejs-sdk/dist/lib/Utils/Base64';
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';

export const NAME = 'git-hub';

export default class GitHubApplication extends ABasicApplication {

    public getName(): string {
        return NAME;
    }

    public getPublicName(): string {
        return 'Git Hub';
    }

    public getDescription(): string {
        return 'Git Hub application';
    }

    public getFormStack(): FormStack {
        const form = new Form(AUTHORIZATION_FORM, 'Authorization settings')
            .addField(new Field(FieldType.TEXT, USER, ' User name', undefined, false))
            .addField(new Field(FieldType.TEXT, TOKEN, ' Token', undefined, false));

        return new FormStack().addForm(form);
    }

    public getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        url?: string,
        data?: unknown,
    ): RequestDto {
        const request = new RequestDto(`https://api.github.com${url}`, method, dto);
        const form = applicationInstall.getSettings()[AUTHORIZATION_FORM] ?? {};
        request.setHeaders({
            [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
            [CommonHeaders.ACCEPT]: JSON_TYPE,
            [CommonHeaders.AUTHORIZATION]: encode(`${form[USER] ?? ''}:${form[TOKEN] ?? ''}`),
        });

        if (data) {
            request.setJsonBody(data);
        }

        return request;
    }

}


```
</TabItem>
<TabItem value="php" label="PHP">

```php
namespace Pipes\PhpSdk\Application;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;

final class GitHubApplication extends BasicApplicationAbstract
{

    public const NAME = 'git-hub';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPublicName(): string
    {
        return 'Git hub';
    }

    public function getDescription(): string
    {
        return 'Git Hub application';
    }

    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $form = $applicationInstall->getSettings()[self::AUTHORIZATION_FORM] ?? [];

        return new RequestDto(
            new Uri(sprintf('https://api.github.com%s', $url)),
            $method,
            $dto,
            $data ?? '',
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => base64_encode(
                    sprintf('%s:%s', $form[self::USER] ?? '', $form[self::TOKEN] ?? ''),
                ),
            ],
        );
    }

    public function getFormStack(): FormStack
    {
        $authForm = new Form(self::AUTHORIZATION_FORM, 'Authorization settings');
        $authForm
            ->addField(new Field(Field::TEXT, self::USER, 'Username', NULL, FALSE))
            ->addField(new Field(Field::TEXT, self::TOKEN, 'Token', NULL, FALSE));

        $stack = new FormStack();
        $stack->addForm($authForm);

        return $stack;
    }

}


```
</TabItem>
</Tabs>

## Registrace aplikace v kontejneru

<Tabs>
<TabItem value="typescript" label="Typescript">
The last step is to register our Application into container. This is once again done in index.ts file.

```typescript
// ...
import { container } from '@orchesty/nodejs-sdk';
import GitHubApplication from './GitHubApplication';
// ...

export default async function prepare(): Promise<void> {
    // ...
    const gitHubApplication = new GitHubApplication();
    container.setApplication(gitHubApplication);
    // ...
}
```
</TabItem>
<TabItem value="php" label="PHP">
The last step is to register our Application into container. This is once again done in config folder.

```php
# ./config/application/application.yaml
services:
    hbpf.application.git-hub:
        class: Pipes\PhpSdk\Application\GitHubApplication
```
</TabItem>
</Tabs>

## Zobrazení aplikace v marketplace

Aplikace vytvořené v libovolné službě registrované v Orchesty se zobrazují v [Orchesty marketplace](../orchesty/marketplace.md). Zde je instalujeme pro použití v topologiích a máme zde také k dispozici formuláře, které jsme si v aplikacích připravili pro uživatelská nastavení.

Pokud jsme tedy udělali vše správně, uvidíme nyní naší novou aplikaci v záložce [markteplace](http://127.0.0.10/applications) v UI **Orchesty admin**.

![GitHub application](/img/tutorial/basicApplication/github-application.png "GitHub application")

Když aplikaci nainstalujeme, zpřístupní se nám i vytvořený formulář.

![GitHub form](/img/tutorial/basicApplication/github-form.png "GitHub form")


## Connector creation

Nyní vytvoříme konektor, který bude aplikaci využívat. Konektor  bude očekávat vstupní data pro doplnění URL repozitáře, který bude z GitHub získávat.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import OnRepeatException from '@orchesty/nodejs-sdk/dist/lib/Exception/OnRepeatException';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';

export const NAME = 'github-get-repository';

export default class GitHubGetRepositoryConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const data = dto.getJsonData();
        const appInstall = await this.getApplicationInstallFromProcess(dto);

        if (!data.user || !data.repo) {
            dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Connector has no required data.');
        } else {
            const request = await this.getApplication().getRequestDto(dto, appInstall, HttpMethods.GET, `/repos/${data.user}/${data.repo}`);
            const response = await this.getSender().send(request);

            if (response.getResponseCode() >= 300 && response.getResponseCode() < 400) {
                throw new OnRepeatException(30, 5, response.getBody());
            } else if (response.getResponseCode() >= 400) {
                dto.setStopProcess(ResultCode.STOP_AND_FAILED, `Failed with code ${response.getResponseCode()}`);
            }

            dto.setData(response.getBody());
        }
        return dto;
    }

}

export interface IInput {
    user: string;
    repo: string;
}
```
</TabItem>
<TabItem value="php" label="PHP">

```php
namespace Pipes\PhpSdk\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

final class GitHubRepositoryConnector extends ConnectorAbstract
{

    public const NAME = 'git-hub-get-repository';
    
    function getName(): string
    {
        return self::NAME;
    }
    
    function processAction(ProcessDto $dto): ProcessDto
    {
        $data       = $dto->getJsonData();
        $appInstall = $this->getApplicationInstallFromProcess($dto);

        if (!isset($data['user']) || !isset($data['repo'])) {
            return $dto->setStopProcess(ProcessDtoAbstract::STOP_AND_FAILED, 'Missing required data [user, repo]');
        }

        $request  = $this->getApplication()->getRequestDto(
            $dto,
            $appInstall,
            CurlManager::METHOD_GET,
            sprintf('/repos/%s/%s', $data['user'], $data['repo']),
        );
        $response = $this->getSender()->send($request);
        $dto->setData($response->getBody());

        return $dto;
    }

}
```
</TabItem>
</Tabs>

V  konektoru můžete vidět ošetření chybových odpovědí metodou **setStopProcess**, případně výjimkou **OnRepeatException**. Pro všechny možnosti ošetření chyb konektorů doporučujeme nastudovat na stránce [Results evaluation](../documentation/results-evaluation.md).

## Registrace konektoru aplikace
Nyní musíme zaregistrovat nový konektor v kontejneru. Konektory aplikací vyžadují přístup k databázi a k aplikaci, kterou používají, proto je nesmíme zapomenout nastavit.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import { initiateContainer, listen, container } from '@orchesty/nodejs-sdk';
import GitHubGetRepositoryConnector from './GitHubGetRepositoryConnector';
// ...

export default async function prepare(): Promise<void> {

    // ...
    const curlSender = container.get<CurlSender>(CoreServices.CURL);
    const mongoDbClient = container.get<MongoDbClient>(CoreServices.MONGO);
    const gitHubApplication = new GitHubApplication();
    const gitHubGetRepositoryConnector = new GitHubGetRepositoryConnector();

    gitHubGetRepositoryConnector
        .setSender(curlSender)
        .setDb(mongoDbClient)
        .setApplication(gitHubApplication);

    container.setConnector(gitHubGetRepositoryConnector);
    // ...
}
```
</TabItem>
<TabItem value="php" label="PHP">

```php
# ./config/connector/connector.yaml
services:
    // ...
    hbpf.connector.git-hub-get-repository:
        class: Pipes\PhpSdk\Connector\GitHubRepositoryConnector
        calls:
            - ['setApplication', ['@hbpf.application.git-hub']]
            - ['setSender', ['@hbpf.transport.curl_manager']]
            - ['setDb', ['@doctrine_mongodb.odm.default_document_manager']]
```
</TabItem>
</Tabs>

Note that connector is calling getRequestDto of an application
(given to it by setApplication in index.ts), which will fill required Authorization header.

## Použití konektoru s aplikací v topologii

Nyní naší novou aplikaci otestujeme. Nejprve nesmíme zapomenout naší aplikaci nainstalovat v záložce **Applications**. Pro autorizaci je třeba vyplnit v aplikaci naše přístupvé k API GitHub. Autorizační token vygenerujete v developerském nastavení svého účtu na adrese [https://github.com/settings/tokens](https://github.com/settings/tokens).

V **Orchesty Admin** si vytvoříme novou topologii. Opět v ní nakonec zařadíme **user task**, abychom lépe kontrolovali výstup konektoru.

![GitHub topology](/img/tutorial/basicApplication/github-topology.png "GitHub topology")

Topologii publikujeme a aktivujeme. Pro odeslání bude tentokrát topologie očekávat vložení dat, konkrétně vlastníka a název repozitáře. Protože jsme ale chybové situace ošetřili, můžeme si nejprve vyzkoušet, jak se proces zachová, pokud správná data nevložíme.

## Zachycení chyby

Spustíme tedy nejprve topologii bez dat. Když se nyní podíváme do záložky **Overview** dané topologie, uvidíme, že proces skončil chybou.

![Failed process](/img/tutorial/basicApplication/failed-process.png "Failed process")

Pokud proces skončí chybou, můžeme se podívat do záložky **Logs** pro bližší informace o dané chybě.

![Error log](/img/tutorial/basicApplication/github-error-log.png "Error log")

Chybový stav jsme v konektoru ošetřili kódem, který říká, že proces má skončit a zpráva má být zachycena v **koši**. Přejdeme tedy do záložky **koš**, kde se na zprávu podíváme a můžeme ji i opravit.

![Failed message in trash](/img/tutorial/basicApplication/failed-message-in-trash.png "Failed message in trash")

Mezi hlavičkami můžeme vidět i **result message**, kterou jsme v konektoru nastavili a která nám říká, že konektor neobdržel potřebná data. V tomto případě můžeme data rovnou opravit, resp.vložit a pustit zprávu znovu do konektoru.

![Update in trash](/img/tutorial/basicApplication/update-in-trash.png "Update in trash")

Zprávu uložíme a tlačítkem **Approve** ji odešleme znovu do konektoru. Pokud jsme zadali správný název a vlastníka repozitáře, můžeme nyní vidět, že zpráva úspěšně doběhla do **user task** uzlu za konektorem. 

Samozřejmě můžeme vyzkoušet i spuštění procesu rovnou se správnými daty. Klíče pro vložení dat máme definované v interface konektoru.

![Run topology](/img/tutorial/basicApplication/hubspot-run.png "Run topology")

Gratulujeme! Tím máme vytvořenou první aplikaci s basic autorizací. Aplikaci může nyní využívat libovolný počet konektorů. V příští kapitole si rozšíříme kolekci o konektor pro dávkovou akci, která vyžaduje stránkování.
