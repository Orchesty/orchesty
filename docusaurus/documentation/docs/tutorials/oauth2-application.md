import Image from '/src/components/ThemedImg';
import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# OAuth2 Application

:::danger
This is not a final version!
:::

In this tutorial we'll implement an Application with OAuth 2.0 authorization, which is today
probably the most used authorization to SaaS. Both OAuth1 and OAuth2 are handled via GUI.
Within Admin Orchesty creates complete form including redirect to integrated service.
We chose HubSpot CRM for this tutorial.

### Prerequisites

- [Installed and running Orchesty](../get-started/installation).
- [Connected SDK](SDK-settings)
- [Connector](basic-connector)
- Recommendation to start with [Basic Application](basic-connector)

## Application create

<Tabs>
<TabItem value="typescript" label="Typescript">

First create an Application extending AOAuth2Application and fill all base methods
described in [Basic Application](basic-application) tutorial.

```typescript
// ...
import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import AOAuth2Application from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/OAuth2/AOAuth2Application';
import { CLIENT_ID, CLIENT_SECRET } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/OAuth2/IOAuth2Application';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { BodyInit, Headers } from 'node-fetch';
// ...

const APP_ID = 'app_id';
export const BASE_URL = 'https://api.hubapi.com';
export const NAME = 'hub-spot';

export default class HubSpotApplication extends AOAuth2Application {
    // ...
    
    public getName(): string {
        return NAME;
    }

    public getPublicName(): string {
        return 'HubSpot';
    }

    public getDescription(): string {
        return 'HubSpot application with OAuth 2';
    }

    public getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        url?: string,
        data?: BodyInit,
    ): RequestDto {
        // Implementaci provedeme v dalších krocích.
    }

    public getFormStack(): FormStack {
        const form = new Form(AUTHORIZATION_FORM, 'Authorization settings')
            .addField(new Field(FieldType.TEXT, CLIENT_ID, 'Client Id', null, true))
            .addField(new Field(FieldType.TEXT, CLIENT_SECRET, 'Client Secret', null, true))
            .addField(new Field(FieldType.TEXT, APP_ID, 'Application Id', null, true));

        return new FormStack().addForm(form);
    }
    // ...

}

```
</TabItem>
<TabItem value="php" label="PHP">

First create an Application extending OAuth2ApplicationAbstract and fill all base methods
described in [Basic Application](basic-application) tutorial.

```php
// ...
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\Utils\String\Json;
// ...

final class HubSpotApplication extends OAuth2ApplicationAbstract
{

    public const BASE_URL = 'https://api.hubapi.com';
    public const NAME     = 'hub-spot';

    private const APPLICATION_ID = 'applicationId';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPublicName(): string
    {
        return 'HubSpot';
    }

    public function getDescription(): string
    {
        return 'HubSpot application with OAuth 2';
    }

    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto {
    // Implementaci provedeme v dalších krocích.
    }

    public function getFormStack(): FormStack
    {
        $stack    = new FormStack();
        $authForm = new Form(self::AUTHORIZATION_FORM, 'Authorization settings');
        $authForm
            ->addField(new Field(Field::TEXT, self::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::PASSWORD, self::CLIENT_SECRET, 'Client Secret', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::APPLICATION_ID, 'Application Id', NULL, TRUE));

        return $stack;
    }
    // ...

}

```
</TabItem>
</Tabs>

## OAuth2 authorization

Following steps are unique for OAuth2 authorization, which are required to correctly set up
an application for token fetching.

:::danger
Upravit následující - scopeseparator
:::

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import ScopeSeparatorEnum from '../../lib/Authorization/ScopeSeparatorEnum';
// ...

export default class HubSpotApplication extends AOAuth2Application {
  // ...
    
    public getAuthUrl(): string {
        return 'https://app.hubspot.com/oauth/authorize';
    }

    public getTokenUrl(): string {
        return 'https://api.hubapi.com/oauth/v1/token';
    }

    public getScopes(applicationInstall: ApplicationInstall): string[] {
        return ['contacts'];
    }

    protected getScopesSeparator(): string {
        return ScopeSeparatorEnum.SPACE;
    }
  
  //...

}
```
</TabItem>
<TabItem value="php" label="PHP">

```php
// ...
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;
// ...

final class HubSpotApplication extends OAuth2ApplicationAbstract
{

    // ...
    
    protected const SCOPE_SEPARATOR = ScopeFormatter::SPACE;
    
    public function getAuthUrl(): string
    {
        return 'https://app.hubspot.com/oauth/authorize';
    }

    public function getTokenUrl(): string
    {
        return 'https://api.hubapi.com/oauth/v1/token';
    }
    
    protected function getScopes(ApplicationInstall $applicationInstall): array
    {
        $applicationInstall;

        return ['contacts'];
    }

    // ...

}
```
</TabItem>
</Tabs>

## RequestDto

Now we'll correctly implement Authorization for requestDto.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
export default class HubSpotApplication extends AOAuth2Application {
 //...
 
 public getRequestDto(
  dto: AProcessDto,
  applicationInstall: ApplicationInstall,
  method: HttpMethods,
  url?: string,
  data?: BodyInit,
 ): RequestDto {
  const headers = new Headers({
   [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
   [CommonHeaders.ACCEPT]: JSON_TYPE,
   [CommonHeaders.AUTHORIZATION]: `Bearer ${this.getAccessToken(applicationInstall)}`,
  });

  return new RequestDto(url ?? BASE_URL, method, dto, data, headers);
 }
 
 //...

}
```

</TabItem>
<TabItem value="php" label="PHP">

```php
// ...

final class HubSpotApplication extends OAuth2ApplicationAbstract
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
        if (!$this->isAuthorized($applicationInstall)) {
            throw new AuthorizationException('Unauthorized');
        }

        return new RequestDto(
            new Uri($url ?? self::BASE_URL),
            $method,
            $dto,
            $data ?? '',
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
            ],
        );
    }
    
    // ...

}
```

</TabItem>
</Tabs>

## Celý kód aplikace

Tím máme HubSpot aplikaci připravenou. Celý kód aplikace si můžeme skopírovat zde:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import ApplicationTypeEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/ApplicationTypeEnum';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import AOAuth2Application from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/OAuth2/AOAuth2Application';
import { CLIENT_ID, CLIENT_SECRET } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/OAuth2/IOAuth2Application';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { BodyInit, Headers } from 'node-fetch';

const APP_ID = 'app_id';
export const BASE_URL = 'https://api.hubapi.com';
export const NAME = 'hub-spot';

export default class HubSpotApplication extends AOAuth2Application {

    public getName(): string {
        return NAME;
    }

    public getPublicName(): string {
        return 'HubSpot';
    }

    public getAuthUrl(): string {
        return 'https://app.hubspot.com/oauth/authorize';
    }

    public getTokenUrl(): string {
        return 'https://api.hubapi.com/oauth/v1/token';
    }

    public getDescription(): string {
        return 'HubSpot application with OAuth 2';
    }

    public getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        url?: string,
        data?: BodyInit,
    ): RequestDto {
        const headers = new Headers({
            [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
            [CommonHeaders.ACCEPT]: JSON_TYPE,
            [CommonHeaders.AUTHORIZATION]: `Bearer ${this.getAccessToken(applicationInstall)}`,
        });

        return new RequestDto(url ?? BASE_URL, method, dto, data, headers);
    }

    public getFormStack(): FormStack {
        const form = new Form(AUTHORIZATION_FORM, 'Authorization settings')
            .addField(new Field(FieldType.TEXT, CLIENT_ID, 'Client Id', null, true))
            .addField(new Field(FieldType.TEXT, CLIENT_SECRET, 'Client Secret', null, true))
            .addField(new Field(FieldType.TEXT, APP_ID, 'Application Id', null, true));

        return new FormStack().addForm(form);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public getScopes(applicationInstall: ApplicationInstall): string[] {
        return ['contacts'];
    }
}

```

</TabItem>
<TabItem value="php" label="PHP">

```php
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;

final class HubSpotApplication extends OAuth2ApplicationAbstract
{

    public const BASE_URL = 'https://api.hubapi.com';
    public const NAME     = 'hub-spot';

    private const APPLICATION_ID = 'applicationId';
    
    protected const SCOPE_SEPARATOR = ScopeFormatter::SPACE;

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPublicName(): string
    {
        return 'HubSpot';
    }

    public function getDescription(): string
    {
        return 'HubSpot application with OAuth 2';
    }

    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        if (!$this->isAuthorized($applicationInstall)) {
            throw new AuthorizationException('Unauthorized');
        }

        return new RequestDto(
            new Uri($url ?? self::BASE_URL),
            $method,
            $dto,
            $data ?? '',
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
            ],
        );
    }

    public function getFormStack(): FormStack
    {
        $stack    = new FormStack();
        $authForm = new Form(self::AUTHORIZATION_FORM, 'Authorization settings');
        $authForm
            ->addField(new Field(Field::TEXT, self::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::PASSWORD, self::CLIENT_SECRET, 'Client Secret', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::APPLICATION_ID, 'Application Id', NULL, TRUE));

        return $stack;
    }

    public function getAuthUrl(): string
    {
        return 'https://app.hubspot.com/oauth/authorize';
    }

    public function getTokenUrl(): string
    {
        return 'https://api.hubapi.com/oauth/v1/token';
    }

    protected function getScopes(ApplicationInstall $applicationInstall): array
    {
        $applicationInstall;

        return ['contacts'];
    }

}

```

</TabItem>
</Tabs>

## Register into container

Nesmíme zapomenout registrovat aplikaci do kontejneru.

<Tabs>
<TabItem value="typescript" label="Typescript">

Aplikaci v index.ts zaregistrujeme do kontejneru.

```typescript
import { container, initiateContainer } from '@orchesty/nodejs-sdk';
import TestOAuth2Application from './TestOAuth2Application';

export default async function prepare(): Promise<void> {
  await initiateContainer();

  container.setApplication(new TestOAuth2Application());
}
```
</TabItem>
<TabItem value="php" label="PHP">

Applikaci registrujeme do yaml souboru: "./config/application/application.yaml"

```yaml
# ./config/application/application.yaml
services:
  _defaults:
    autowire: false
    autoconfigure: false
    public: '%public.services%'

  hbpf.application.hub-spot:
    class: Pipes\PhpSdk\Application\HubSpotApplication
    arguments:
      - '@hbpf.providers.oauth2_provider'

  // ...

```
</TabItem>
</Tabs>



Pokud jsme vše provedli správně, v marketplace Orchesty Adminu nyní uvidíme novou aplikaci. Aplikaci nainstalujeme a v jejím nastavení se nám zobrazí formulář pro autorizaci, který jsme vytvořili.

<Image path="/img/tutorial/oauth2/detail-app-hubspot.png" alt="Application" />

Pokud již máme přístupové údaje z HubSpot, můžeme nainstalovanou aplikaci autorizovat a tím bude připravena k použití.

## Connector creation

:::tip
We recommend to first check out [Connector tutorial](basic-connector) to see how create connector to remove API. 
:::

Nyní vytvoříme konektor, který vloží do HubSpot nový kontakt. Celý konektor je velmi jednoduchý, pokud jsme již vytvořili konektory v předchozích návodech, měla by to být pro nás hračka. Ukážeme si tedy rovnou celý kód:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import logger from '@orchesty/nodejs-sdk/dist/lib/Logger/Logger';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { BASE_URL } from './HubSpotApplication';

export const NAME = 'hub-spot-create-contact';

export default class HubSpotCreateContactConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const applicationInstall = await this.getApplicationInstallFromProcess(dto);

        const request = await this.getApplication().getRequestDto(
            dto,
            applicationInstall,
            HttpMethods.POST,
            `${BASE_URL}/crm/v3/objects/contacts`,
            dto.getData(),
        );

        const response = await this.getSender().send<IResponse>(request, [201, 409]);

        if (response.getResponseCode() === 409) {
            const email = dto.getJsonData();
            logger.error(`Contact "${email}" already exist.`, dto);
        }

        return dto.setData(response.getBody());
    }

}

interface IResponse {
    properties: {
        email: string;
    };
}

```

</TabItem>
<TabItem value="php" label="PHP">

```php
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Pipes\PhpSdk\Application\HubSpotApplication;

final class HubSpotCreateContactConnector extends ConnectorAbstract
{

    public const NAME = 'hub-spot-create-contact';

    function getName(): string
    {
        return self::NAME;
    }

    function processAction(ProcessDto $dto): ProcessDto
    {
        $appInstall = $this->getApplicationInstallFromProcess($dto);
        $request    = $this->getApplication()->getRequestDto(
            $dto,
            $appInstall,
            CurlManager::METHOD_POST,
            sprintf('%s/crm/v3/objects/contacts', HubSpotApplication::BASE_URL),
            $dto->getData(),
        );

        $response = $this->getSender()->send($request, [201, 409]);
        $dto->setData($response->getBody());

        return $dto;
    }

}

```

</TabItem>
</Tabs>

V metodě `send()` si můžeme všimnout zkráceného zápisu pro nastavení opakovaných volání. V tomto případě konektor opakuje všechna volání, která se vrátí s jiným kódem, než jsme definovali. Dalšími parametry můžeme nastavit interval a počet opakování. My jsme ponechali výchozí hodnoty, tedy 10 opakování po 60 sec.

:::tip
Vše o nastavení opakovaných volání se dozvíme v kapitole [Repeater](../documentation/repeater.md).
:::

Nakonec jsme v konektoru nastavili logování pro případ, že nový kontakt již v HubSpot existuje. Jak s touto situací naložíme v praxi je samozřejmě na nás.

:::tip
Doporučujeme nastudovat dokumentaci k [logování v Orchesty](../documentation/logs.md).
:::

## Registrace konektoru

<Tabs>
<TabItem value="typescript" label="Typescript">

Nakonec konektor v `index.ts` zaregistrujeme do kontejneru .

```typescript
//...

import HubSpotCreateContactConnector from './HubSpotCreateContactConnector';

const prepare = async (): Promise<void> => {
    //...

    const hubSpotCreateContactConn = new HubSpotCreateContactConnector();

    hubSpotCreateContactConn
        .setSender(curlSender)
        .setDb(mongoDbClient)
        .setApplication(hubSpotApplication);

    container.setConnector(hubSpotCreateContactConn);

    //...
}
```

</TabItem>
<TabItem value="php" label="PHP">

Nakonec konektor registrujeme do yaml souboru: "./config/connector/connector.yaml"

```yaml
# ./config/connector/connector.yaml
services:
  _defaults:
    autowire: false
    autoconfigure: false
    public: '%public.services%'

  hbpf.connector.hub-spot-create-contact:
    class: Pipes\PhpSdk\Connector\HubSpotCreateContactConnector
    calls:
      - ['setApplication', ['@hbpf.application.hub-spot']]
      - ['setSender', ['@hbpf.transport.curl_manager']]
      - ['setDb', ['@doctrine_mongodb.odm.default_document_manager']]

    // ...

```

</TabItem>
</Tabs>

Tím máme vše připraveno. Nyní můžeme otestovat vložení kontaktu do HubSpot s OAuth2 autentizací.

## Vytvoření topologie

Topologie pro otestování našeho příkladu bude tentokrát opravdu jednoduchá. Použijeme jen start event a náš konektor. Data pro tentokrát vložíme ručně.

![Create contact HubSpot topology](/img/tutorial/oauth2/create-user-topology.png)

