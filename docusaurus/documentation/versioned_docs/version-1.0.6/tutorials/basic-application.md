import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';


# Basic Application

In previous tutorial we've create a simple connector without any authorization. Connector requiring authorizations are usually supported via Application class. 

The **Application** is a means of authorising calls. It provides a form for setting access credentials and any other user settings. Above that, it can contain everything that is common to its connectors.

In this tutorial we'll create a BasicAuthorization application which will prepare HTTP request filling up Authorization header. Also we'll create a simple UI form for authorization settings. This time we'll connect to **GitHub**. 

## Prerequisites

- [Installed and running Orchesty](../get-started/installation).
- [Connected SDK](SDK-settings)

## Creating application 

<Tabs>
<TabItem value="typescript" label="Typescript">

First, we create an application class in the `src` folder, inheriting from `ABasicApplication`.

```typescript
import { ABasicApplication } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';

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

First we create an application class in the `src` folder, inheriting from `BasicApplicationAbstract`.

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

The methods in this part of the code are required. The `name` attribute serves as a unique identifier for the application. The `publicName` and `description` are displayed in Orchesty marketplace.
<!-- TODO Add link [Orchesty marketplace](../admin/marketplace.md), once that page will be written -->

## Form
For each application, we can create any number of [forms](../documentation/form.md) for user communication settings. In our example, we will create one form for entering GitHub account credentials. In the form, we need to enter a token that we can generate in our GitHub account.



<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import CoreFormsEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import {
    ABasicApplication,
    TOKEN,
} from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';

// ...
export default class GitHubApplication extends ABasicApplication {

    // ...
    public getFormStack(): FormStack {
        const form = new Form(AUTHORIZATION_FORM, 'Authorization settings')
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

Next step is finishing method for setting up `RequestDto` for connectors. This method will fill authorization header and returns fully built `RequestDto` object ready to be send. URL, method and body provides connector calling this Application. 

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
// ...

export default class GitHubApplication extends ABasicApplication {

    // ...
    public getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        uri?: string,
        data?: unknown,
    ): RequestDto {
        const request = new RequestDto(`https://api.github.com${uri}`, method, dto);
        if (!this.isAuthorized(applicationInstall)) {
            throw new Error(`Application [${this.getPublicName()}] is not authorized!`);
        }
        const form = applicationInstall.getSettings()[CoreFormsEnum.AUTHORIZATION_FORM] ?? {};
        request.setHeaders({
            [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
            [CommonHeaders.ACCEPT]: 'application/vnd.github+json',
            [CommonHeaders.AUTHORIZATION]: `Bearer ${form[TOKEN]}`,
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
        string             $method,
        ?string            $url = NULL,
        ?string            $data = NULL,
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
                'Accept'        => 'application/vnd.github+json',
                'Authorization' => sprintf('Bearer %s', $form[self::TOKEN]),
            ],
        );
    }
    // ...
    
}
```
</TabItem>
</Tabs>

## Full application code
That's all.  Below you can see the full application code.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import CoreFormsEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import {
    ABasicApplication,
    TOKEN,
} from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
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
        const form = new Form(CoreFormsEnum.AUTHORIZATION_FORM, 'Authorization settings')
            .addField(new Field(FieldType.TEXT, TOKEN, ' Token', undefined, true));

        return new FormStack().addForm(form);
    }

    public getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        uri?: string,
        data?: unknown,
    ): RequestDto {
        const request = new RequestDto(`https://api.github.com${uri}`, method, dto);
        if (!this.isAuthorized(applicationInstall)) {
            throw new Error(`Application [${this.getPublicName()}] is not authorized!`);
        }
        const form = applicationInstall.getSettings()[CoreFormsEnum.AUTHORIZATION_FORM] ?? {};
        request.setHeaders({
            [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
            [CommonHeaders.ACCEPT]: 'application/vnd.github+json',
            [CommonHeaders.AUTHORIZATION]: `Bearer ${form[TOKEN]}`,
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
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;

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
        string             $method,
        ?string            $url = NULL,
        ?string            $data = NULL,
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
                'Accept'        => 'application/vnd.github+json',
                'Authorization' => sprintf('Bearer %s', $form[self::TOKEN]),
            ],
        );
    }

    public function getFormStack(): FormStack
    {
        $authForm = new Form(self::AUTHORIZATION_FORM, 'Authorization settings');
        $authForm
            ->addField(new Field(Field::TEXT, self::TOKEN, 'Token', NULL, TRUE));

        $stack = new FormStack();
        $stack->addForm($authForm);

        return $stack;
    }

}


```
</TabItem>
</Tabs>

## Registering an application in a container

<Tabs>
<TabItem value="typescript" label="Typescript">
The last step is to register our Application into container. This is once again done in `index.ts` file.

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
The last step is to register our Application into container. This is once again done in `config` folder.

```php
# ./config/application.yaml
services:
    _defaults:
        public: '%public.services%'
        
    hbpf.application.git-hub:
        class: Pipes\PhpSdk\Application\GitHubApplication
```
</TabItem>
</Tabs>

## View the app in the marketplace

Applications created in any worker are displayed in the Orchesty marketplace. This is where we install them for use in topologies, and where we also have forms that we have prepared in the applications for user settings.

So if we have done everything correctly, we will now see our new application in the **Applications** tab in the Admin.

![GitHub application](/img/tutorial/basicApplication/github-application.svg "GitHub application")

When we install the application, the form we have created will also be available.

![GitHub form](/img/tutorial/basicApplication/github-form.svg "GitHub form")


## Connector creation

Now we will create a connector that will be used by the application. The connector will download a specific repository and will expect input data to complete the URL.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import OnRepeatException from '@orchesty/nodejs-sdk/dist/lib/Exception/OnRepeatException';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import GitHubApplication from "./GitHubApplication";

export const NAME = 'github-get-repository';

export default class GitHubGetRepositoryConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const data = dto.getJsonData();
        const appInstall = await this.getApplicationInstallFromProcess(dto);

        if (!data.org || !data.repo) {
            dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Connector has no required data.');
        } else {
            const request = await this.getApplication<GitHubApplication>().getRequestDto(dto, appInstall, HttpMethods.GET, `/repos/${data.org}/${data.repo}`);
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
    org: string;
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

        if (!isset($data['org']) || !isset($data['repo'])) {
            return $dto->setStopProcess(ProcessDtoAbstract::STOP_AND_FAILED, 'Missing required data [user, repo]');
        }

        $request  = $this->getApplication()->getRequestDto(
            $dto,
            $appInstall,
            CurlManager::METHOD_GET,
            sprintf('/repos/%s/%s', $data['org'], $data['repo']),
        );
        $response = $this->getSender()->send($request);
        $dto->setData($response->getBody());

        return $dto;
    }

}
```
</TabItem>
</Tabs>

In the connector you can see the handling of error responses by the `setStopProcess` method or by the `OnRepeatException` exception. For all the connector error handling options, we recommend to study the [Results evaluation](../documentation/results-evaluation.md) page.

## Application connector registration
Now we need to register a new connector in the container. Application connectors require access to the database and to the application they use, so we must not forget to set them up.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import { initiateContainer, listen, container } from '@orchesty/nodejs-sdk';
import GitHubGetRepositoryConnector from './GitHubGetRepositoryConnector';
import MongoDbClient from '@orchesty/nodejs-sdk/dist/lib/Storage/Mongodb/Client';
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
# ./config/connector.yaml
services:
    _defaults:
        public: '%public.services%'
        
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

Note that connector is calling `getRequestDto` of an application
(given to it by `setApplication` in `index.ts`), which will fill required authorization header.

## Using a connector with an application in the topology

We will now test our new application. First, we must remember to install our application in the **Applications** tab. For authorization, we need to fill in our GitHub API accesses in the application. You can generate an authorization token in the developer settings of your account at [https://github.com/settings/tokens](https://github.com/settings/tokens) and insert it into the authorization form of our new application. Then we still must activate the application.

![Authorize application](/img/tutorial/authorize-github.svg "Authorize application")

In **Orchesty Admin** we will create a new topology. Again, we'll include a **user task** at the end to better control the output of the connector.

![GitHub topology](/img/tutorial/basicApplication/github-topology.svg "GitHub topology")

We publish and activate the topology. This time, the topology will expect data to be inserted for submission, namely the owner and the repository name. Since we have handled error situations, we can first test how the process behaves if we don't insert the correct data.



## Error handling

So let's run the topology without data first. If we now look at the **Processes** tab of the topology, we can see that the process ended with an error.

![Failed process](/img/tutorial/basicApplication/failed-process.svg "Failed process")

If the process ends with an error, we can look at the **Log list** tab for more information about the error.

![Error log](/img/tutorial/basicApplication/github-error-log.svg "Error log")

The error status was handled by a code in the connector, which says that the process should be terminated and the message should be catched in the **trash**. So we go to the **Trash** tab, where we can look at the message and even fix it.

![Failed message in trash](/img/tutorial/basicApplication/failed-message-in-trash.svg "Failed message in trash")

Among the headers we can also see the **result message** that we have set in the connector, which tells us that the connector has not received the necessary data. In this case, we can correct the data directly, or insert and drop the message into the connector again.

![Update in trash](/img/tutorial/basicApplication/update-in-trash.svg "Update in trash")

We save the message and use the **Approve** button to send it again to the connector. If we have entered the correct name and owner of the repository, we can now see that the message has successfully reached the **user task** node behind the connector.

We can also try running the process directly with the correct data. We have defined the keys to insert the data in the connector interface.

![Run topology](/img/tutorial/basicApplication/hubspot-run.svg "Run topology")

Congratulations! This is our first application with basic authorization. The application can now be used by any number of connectors.
