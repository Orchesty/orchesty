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

First create an Application extending AOAuth2Application and fill all base methods
described in [Basic Application](basic-application) tutorial.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import AOAuth2Application from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/OAuth2/AOAuth2Application';
import HttpMethods from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import { CLIENT_ID, CLIENT_SECRET } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/OAuth2/IOAuth2Application';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import { BodyInit, Headers } from 'node-fetch';
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';

const APP_ID = 'app_id';
export const BASE_URL = 'https://api.hubapi.com';

export default class HubSpotApplication extends AOAuth2Application {
 public getName = (): string => 'hub-spot';

 public getPublicName = (): string => 'HubSpot';
 
 // eslint-disable-next-line max-len
 public getDescription = (): string => 'HubSpot application with OAuth 2';
 
 public getFormStack = (): FormStack => {
  const form = new Form(AUTHORIZATION_FORM, 'Authorization settings')
          .addField(new Field(FieldType.TEXT, CLIENT_ID, 'Client Id', null, true))
          .addField(new Field(FieldType.TEXT, CLIENT_SECRET, 'Client Secret', null, true))
          .addField(new Field(FieldType.TEXT, APP_ID, 'Application Id', null, true));

  return new FormStack().addForm(form);
 };
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
import ScopeSeparatorEnum from '../../lib/Authorization/ScopeSeparatorEnum';

export default class HubSpotApplication extends AOAuth2Application {
  // ...

  public getAuthUrl = (): string => 'https://app.hubspot.com/oauth/authorize';

  public getTokenUrl = (): string => 'https://api.hubapi.com/oauth/v1/token';

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  public getScopes = (applicationInstall: ApplicationInstall): string[] => ['contacts'];

  protected _getScopesSeparator = (): string => ScopeSeparatorEnum.SPACE;
  
  //...

}
```
</TabItem>
</Tabs>

## RequestDto

Now we'll correctly implement Authorization for requestDto.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
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
</Tabs>

## Celý kód aplikace

Tím máme HubSpot aplikaci připravenou. Celý kód aplikace si můžeme skopírovat zde:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import AOAuth2Application from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/OAuth2/AOAuth2Application';
import HttpMethods from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import { CLIENT_ID, CLIENT_SECRET } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/OAuth2/IOAuth2Application';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import { BodyInit, Headers } from 'node-fetch';
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';

const APP_ID = 'app_id';
export const BASE_URL = 'https://api.hubapi.com';

export default class HubSpotApplication extends AOAuth2Application {
 public getName = (): string => 'hub-spot';

 public getPublicName = (): string => 'HubSpot';

 public getAuthUrl = (): string => 'https://app.hubspot.com/oauth/authorize';

 public getTokenUrl = (): string => 'https://api.hubapi.com/oauth/v1/token';

 // eslint-disable-next-line max-len
 public getDescription = (): string => 'HubSpot application with OAuth 2';

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

 public getFormStack = (): FormStack => {
  const form = new Form(AUTHORIZATION_FORM, 'Authorization settings')
          .addField(new Field(FieldType.TEXT, CLIENT_ID, 'Client Id', null, true))
          .addField(new Field(FieldType.TEXT, CLIENT_SECRET, 'Client Secret', null, true))
          .addField(new Field(FieldType.TEXT, APP_ID, 'Application Id', null, true));

  return new FormStack().addForm(form);
 };

 // eslint-disable-next-line @typescript-eslint/no-unused-vars
 public getScopes = (applicationInstall: ApplicationInstall): string[] => ['contacts'];
}

```

</TabItem>
</Tabs>

## Register into container

Nesmíme zapomenout registrovat aplikaci do kontejneru.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import { container, initiateContainer } from '@orchesty/nodejs-sdk';
import TestOAuth2Application from './TestOAuth2Application';

export default async function prepare(): Promise<void> {
  await initiateContainer();

  container.setApplication(new TestOAuth2Application());
}
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

```typescript
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import HttpMethods from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import logger from '@orchesty/nodejs-sdk/dist/lib/Logger/Logger';
import { BASE_URL } from './HubSpotApplication';

export default class HubSpotCreateContactConnector extends AConnector {
  public getName = (): string => 'hub-spot-create-contact';

  public async processAction(_dto: ProcessDto): Promise<ProcessDto> {
    const dto = _dto;
    const applicationInstall = await this._getApplicationInstallFromProcess(dto);

    const request = await this._application.getRequestDto(
      dto,
      applicationInstall,
      HttpMethods.POST,
      `${BASE_URL}/crm/v3/objects/contacts`,
      dto.data,
    );

    const response = await this._sender.send(request, [201, 409]);

    if (response.responseCode === 409) {
      const email = dto.jsonData as { properties: { email: string } };
      logger.error(`Contact "${email}" already exist.`, dto);
    }

    dto.data = response.body;
    return dto;
  }
}

```

V metodě `_sender.send()` si můžeme všimnout zkráceného zápisu pro nastavení opakovaných volání. V tomto případě konektor opakuje všechna volání, která se vrátí s jiným kódem, než jsme definovali. Dalšími parametry můžeme nastavit interval a počet opakování. My jsme ponechali výchozí hodnoty, tedy 10 opakování po 60 sec.

:::tip
Vše o nastavení opakovaných volání se dozvíme v kapitole [Repeater](../documentation/repeater.md).
:::

Nakonec jsme v konektoru nastavili logování pro případ, že nový kontakt již v HubSpot existuje. Jak s touto situací naložíme v praxi je samozřejmě na nás.

:::tip
Doporučujeme nastudovat dokumentaci k [logování v Orchesty](../documentation/logs.md).
:::

## Registrace konektoru

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

Tím máme vše připraveno. Nyní můžeme otestovat vložení kontaktu do HubSpot s OAuth2 autentizací.

## Vytvoření topologie

Topologie pro otestování našeho příkladu bude tentokrát opravdu jednoduchá. Použijeme jen start event a náš konektor. Data pro tentokrát vložíme ručně.

![Create contact HubSpot topology](/img/tutorial/oauth2/create-user-topology.png)

