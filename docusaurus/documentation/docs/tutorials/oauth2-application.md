import Image from '/src/components/ThemedImg';
import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# OAuth2 Application

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

First, we create the AOAuth2Application extension application and complete all the basic methods described in the [Basic Application](basic-application) tutorial. We also prepare a form for entering access credentials for authorization.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import CoreFormsEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
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
import { BodyInit } from 'node-fetch';

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
        // Implementated in next stepts
    }

    public getFormStack(): FormStack {
        const form = new Form(CoreFormsEnum.AUTHORIZATION_FORM, 'Authorization settings')
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
    // Implementated in next stepts
    }

    public function getFormStack(): FormStack
    {
        $stack    = new FormStack();
        $authForm = new Form(self::AUTHORIZATION_FORM, 'Authorization settings');
        $authForm
            ->addField(new Field(Field::TEXT, self::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::PASSWORD, self::CLIENT_SECRET, 'Client Secret', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::APPLICATION_ID, 'Application Id', NULL, TRUE));

        $stack->addForm($authForm);

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

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import ScopeSeparatorEnum from '@orchesty/nodejs-sdk/dist/lib/Authorization/ScopeSeparatorEnum';
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

Now we'll correctly implement Authorization for `requestDto`.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import { BodyInit, Headers } from 'node-fetch';
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

## Full application code

That's the HubSpot application ready to go. You can copy the full application code here:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import CoreFormsEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import AOAuth2Application from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/OAuth2/AOAuth2Application';
import ScopeSeparatorEnum from '@orchesty/nodejs-sdk/dist/lib/Authorization/ScopeSeparatorEnum';
import { CLIENT_ID, CLIENT_SECRET } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/OAuth2/IOAuth2Application';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import { BodyInit, Headers } from 'node-fetch';
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';

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
        const form = new Form(CoreFormsEnum.AUTHORIZATION_FORM, 'Authorization settings')
            .addField(new Field(FieldType.TEXT, CLIENT_ID, 'Client Id', null, true))
            .addField(new Field(FieldType.TEXT, CLIENT_SECRET, 'Client Secret', null, true))
            .addField(new Field(FieldType.TEXT, APP_ID, 'Application Id', null, true));

        return new FormStack().addForm(form);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public getScopes(applicationInstall: ApplicationInstall): string[] {
        return ['contacts'];
    }

    protected getScopesSeparator(): string {
        return ScopeSeparatorEnum.SPACE;
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
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;

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

        $stack->addForm($authForm);

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

We must not forget to register the application to the container.

<Tabs>
<TabItem value="typescript" label="Typescript">

Register the application in `index.ts` to the container.

```typescript
// ...
import { OAuth2Provider } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Provider/OAuth2/OAuth2Provider';
import { container, initiateContainer } from '@orchesty/nodejs-sdk';
import HubSpotApplication from './HubSpotApplication';

export default async function prepare(): Promise<void> {
    await initiateContainer();

    const oAuth2Provider = container.get<OAuth2Provider>(CoreServices.OAUTH2_PROVIDER);

    // ...
    const hubSpotApplication = new HubSpotApplication(oAuth2Provider);
    container.setApplication(hubSpotApplication);
}
```
</TabItem>
<TabItem value="php" label="PHP">

Register the application in the yaml file: `./config/application/application.yaml`.

```php
# ./config/application.yaml
services:
  _defaults:
    public: '%public.services%'

  hbpf.application.hub-spot:
    class: Pipes\PhpSdk\Application\HubSpotApplication
    arguments:
      - '@hbpf.providers.oauth2_provider'

  // ...

```
</TabItem>
</Tabs>

If we have done everything correctly, we will now see the new app in the marketplace. We install the app and in the settings we will see the authorization form we created.

<Image path="/img/tutorial/oauth2/detail-app-hubspot.svg" alt="Application" />

If we already have HubSpot credentials, we can authorize the installed application and it will be ready for use.

## Connector creation

:::tip
We recommend to first check out [Connector tutorial](basic-connector) to see how create connector to remove API. 
:::

Now we create a connector that inserts a new contact into the HubSpot. The whole connector is very simple, if we have already created connectors in the previous tutorials, this should be a piece of cake for us. So let's show the whole code right away:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import logger from '@orchesty/nodejs-sdk/dist/lib/Logger/Logger';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import HubSpotApplication, { BASE_URL } from './HubSpotApplication';

export const NAME = 'hub-spot-create-contact';

export default class HubSpotCreateContactConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const applicationInstall = await this.getApplicationInstallFromProcess(dto);

        const request = await this.getApplication<HubSpotApplication>().getRequestDto(
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
namespace Pipes\PhpSdk\Connector;

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

In the `send()` method we can notice a shortened notation for setting up repeated calls. In this case, the connector repeats any calls that return with a different code than we defined. We can use other parameters to set the interval and number of retries. We have left the default values, i.e. 10 repeats per 60 sec.

:::tip
All about setting up repeat calls can be found in the [Results evaluation](../documentation/results-evaluation) chapter.
:::

Finally, we set up logging in the connector in case a new contact already exists in HubSpot. How we handle this situation in practice is of course up to us.

:::tip
We recommend studying the documentation for [logging in Orchesty](../documentation/logs.md).
:::

## Registration of connector

<Tabs>
<TabItem value="typescript" label="Typescript">

Finally, we register the connector in `index.ts` to the container.

```typescript
//...

import HubSpotCreateContactConnector from './HubSpotCreateContactConnector';

export default async function prepare(): Promise<void> {
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

Finally, register the connector in the yaml file `./config/connector/connector.yaml`.

```php

# ./config/connector.yaml
services:
  _defaults:
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

We're all set. Now we can test inserting a contact into HubSpot with OAuth 2 authentication.

## Test
First, we need to authorize our HubSpot application. In order to gain access using OAuth, we first need to create a developer account in HubSpot and a new application in that account. In the application settings we then get the **app ID**, **client ID** and **client sercret**. We can find instructions on the [Hubspot documentation](https://developers.hubspot.com/docs/api/working-with-oauth).

We use the credentials to authorize our HubSpot application form in Orchesty Admin.

![HubSpot settings](/img/tutorial/oauth2/hubspot-oauth-settings.svg "HubSpot settings")

Now we can authorize access to our HubSpot account. Orchesty will redirect us to the authorization form.

![Authorize form](/img/tutorial/oauth2/authorize-form.svg "Authorize form")

If we have successfully authorized access to our HubSpot account, we can activate the application and continue to create the topology.

## Creating a topology

The topology to test our example will be really simple this time. We will use the start event and our connector.  For this time, we'll enter the data manually. Finally, we add a user task to check the HubSpot response.

![Create contact HubSpot topology](/img/tutorial/oauth2/create-user-topology.svg)

We save, publish and activate the topology. On run, we specify the email we will send to HubSpot.

![Run topology](/img/tutorial/oauth2/run-create-user.svg "Run topology")

Now we can check the new contact in our HubSpot account.


