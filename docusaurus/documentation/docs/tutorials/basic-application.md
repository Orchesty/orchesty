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

Nejprve vytvoříme ve složce **src** třídu aplikace, dědící z **ABasicApplication**. 

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { ABasicApplication } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';

export default class GitHubApplication extends ABasicApplication {
    public getName = (): string => 'git-hub';
    public getPublicName = (): string => 'Git Hub';
    public getDescription = (): string => 'Git Hub application';
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
import { USER } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
// ...

// ...
export const USER = 'user';
export const TOKEN = 'token';
// ...

export default class GitHubApplication extends ABasicApplication {

  // ...
    public getFormStack = (): FormStack => {
        const form = new Form(AUTHORIZATION_FORM, 'Authorization settings')
            .addField(new Field(FieldType.TEXT, USER, ' User name', undefined, true))
            .addField(new Field(FieldType.TEXT, TOKEN, ' Token', undefined, true));

        return new FormStack().addForm(form);
    };
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
import { JSON_TYPE, CommonHeaders } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import { parseHttpMethod } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
// ...

export default class GitHubApplication extends ABasicApplication {

  // ...
    public getRequestDto = (
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        _url?: string,
        data?: unknown,
    ): RequestDto => {
        const request = new RequestDto(`https://api.github.com${_url}`, method, dto);
        request.headers = {
            [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
            [CommonHeaders.ACCEPT]: JSON_TYPE,
            [CommonHeaders.AUTHORIZATION]: encode(`${TOKEN}:${USER}`),
        };

        if (data) {
            request.setJsonBody(data);
        }

        return request;
    };
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
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import HttpMethods from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { ABasicApplication } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import { encode } from '@orchesty/nodejs-sdk/dist/lib/Utils/Base64';

export const USER = 'user';
export const NAME = 'git-hub';
export const TOKEN = 'token';

export default class GitHubApplication extends ABasicApplication {
    public getName = (): string => NAME;
    public getPublicName = (): string => 'Git Hub';
    public getDescription = (): string => 'Git Hub application';

    public getFormStack = (): FormStack => {
        const form = new Form(AUTHORIZATION_FORM, 'Authorization settings')
            .addField(new Field(FieldType.TEXT, USER, ' User name', undefined, true))
            .addField(new Field(FieldType.TEXT, TOKEN, ' Token', undefined, true));

        return new FormStack().addForm(form);
    };

    public getRequestDto = (
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        _url?: string,
        data?: unknown,
    ): RequestDto => {
        const request = new RequestDto(`https://api.github.com${_url}`, method, dto);
        request.headers = {
            [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
            [CommonHeaders.ACCEPT]: JSON_TYPE,
            [CommonHeaders.AUTHORIZATION]: encode(`${TOKEN}:${USER}`),
        };

        if (data) {
            request.setJsonBody(data);
        }

        return request;
    };
}

```
</TabItem>
</Tabs>

## Registrace aplikace v kontejneru

The last step is to register our Application into container. This is once again done in index.ts file.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import { container } from '@orchesty/nodejs-sdk';
import GitHubApplication from './GitHubApplication';
// ...

export default async function prepare(): Promise<void> {
  // ...
  container.setApplication(new GitHubApplication());
  // ...
}
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
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import HttpMethods from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import OnRepeatException from '@orchesty/nodejs-sdk/dist/lib/Exception/OnRepeatException';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';

export default class GitHubGetRepositoryConnector extends AConnector {
    public getName = () => 'github-get-repository';

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.jsonData as IInput;
        const appInstall = await this._getApplicationInstall();

        if (!data.user || !data.repo) {
            dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Connector has no required data.');
        } else {
            const request = await this._application.getRequestDto(dto, appInstall, HttpMethods.GET, '/repos/' + data.user + '/' + data.repo);
            const response = await this._sender.send(request);

            if (response.responseCode >= 300 && response.responseCode < 400) {
                throw new OnRepeatException(30, 5, response.body);
            } else if (response.responseCode >= 400) {
                dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Failed with code ' + response.responseCode);
            }

            dto.data = response.body;
        }
        return dto;
    }
}

interface IInput {
    user: string
    repo: string
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
import GitHubGetRepositoryConnector from "./GitHubGetRepositoryConnector";
// ...

export default async function prepare(): Promise<void> {

  // ...
    const mongoDbClient = container.get(CoreServices.MONGO);
    const curlSender = container.get(CoreServices.CURL);
    const gitHubApplication = new GitHubApplication();
    const gitHubGetRepositoryConn = new GitHubGetRepositoryConnector();

    gitHubGetRepositoryConn
        .setSender(curlSender)
        .setDb(mongoDbClient)
        .setApplication(gitHubApplication);

    container.setConnector(gitHubGetRepositoryConn);
  // ...
}
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
