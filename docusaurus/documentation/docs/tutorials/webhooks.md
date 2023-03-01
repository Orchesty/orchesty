import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Webhooks

In this tutorial, we will show how to register webhooks to get realtime information about events in integrated services. For the demonstration, we will again use the GitHub application we created in the [Basic Application](../tutorials/basic-application.md) tutorial. If you haven't read this tutorial, we recommend you study it first.

## Principle
The principle of webhooks is simple. We tell the integrated service on its API where to send the requested event. For this to work, we need to have the authorization of the integrated service sorted out first.

We handle the registration and unregistration of webhooks in Orchesty directly within the application.

## Webhooks application
First, we need to change type of Application by override `getApplicationType` method and add the application interface `IWebhookApplication`. Next step is create a `RequestDto` object in the new `getWebhookSubscribeRequestDto` method to subscribe the webhook and a `processWebhookSubscribeResponse` method to handle the registration.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import ApplicationTypeEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/ApplicationTypeEnum';
import { IWebhookApplication } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/IWebhookApplication';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import WebhookSubscription from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Webhook/WebhookSubscription';
import ResponseDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/ResponseDto';

// ...

export default class GitHubApplication extends ABasicApplication implements IWebhookApplication {
    
    // ...

    public getApplicationType(): ApplicationTypeEnum {
        return ApplicationTypeEnum.WEBHOOK;
    }

    public getWebhookSubscribeRequestDto(
        applicationInstall: ApplicationInstall,
        subscription: WebhookSubscription,
        url: string,
    ): RequestDto {
      const request = new ProcessDto();
      const { owner, record } = subscription.getParameters();
      return this.getRequestDto(
        request,
        applicationInstall,
        HttpMethods.POST,
        `/repos/${owner}/${record}/hooks`,
        {
          config: {
            url,
            content_type: 'json',
          },
          name: 'web',
          events: [subscription.getName()],
        },
      );
    }

    public processWebhookSubscribeResponse(
        dto: ResponseDto,
        applicationInstall: ApplicationInstall,
    ): string {
        if (dto.getResponseCode() !== 201) {
          throw new Error((dto.getJsonBody() as { message: string }).message);
        }
      
        return (dto.getJsonBody() as { id: string }).id;
    }
    
    // ...
    
}
```
</TabItem>
<TabItem value="php" label="PHP">

```php
// ...
use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
// ...

final class GitHubApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{

    // ...
    
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK->value;
    }
    
    public function getWebhookSubscribeRequestDto(
        ApplicationInstall  $applicationInstall,
        WebhookSubscription $subscription,
        string              $url,
    ): RequestDto
    {
        $request    = new ProcessDto();
        $parameters = $subscription->getParameters();

        return $this->getRequestDto(
            $request,
            $applicationInstall,
            CurlManager::METHOD_POST,
            sprintf('/repos/%s/%s/hooks', $parameters['owner'] ?? '', $parameters['record'] ?? ''),
            Json::encode(
                [
                    'config' => [
                        'url'          => $url,
                        'content_type' => 'json',
                    ],
                    'name'   => 'web',
                    'events' => [$subscription->getName()],
                ],
            ),
        );
    }

    public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string
    {
        $install;
        
        if ($dto->getStatusCode() !== 201) {
            throw new Exception($dto->getJsonBody()['message']);
        }

        return $dto->getJsonBody()['id'] ?? '';
    }
    
    // ...

}


```

</TabItem>
</Tabs>


## Unsubscribe
We do the same for unsubscribe webhooks.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
// ...
import Webhook from '@orchesty/nodejs-sdk/dist/lib/Application/Database/Webhook';

export default class GitHubApplication extends ABasicApplication implements IWebhookApplication {
    
    // ...

    public getWebhookUnsubscribeRequestDto(applicationInstall: ApplicationInstall, webhook: Webhook): RequestDto {
      const webhookSubscription = this.getWebhookSubscriptions().find(
        (item) => item.getName() === webhook.getName(),
      );
      if (!webhookSubscription) {
        throw new Error(`Webhook with name [${webhook.getName()}] has not been found.`);
      }

      const { record, owner } = webhookSubscription.getParameters();

      const request = new ProcessDto();
      return this.getRequestDto(
        request,
        applicationInstall,
        HttpMethods.DELETE,
        `/repos/${owner}/${record}/hooks/${webhook.getWebhookId()}`,
      );
    }
    
    public processWebhookUnsubscribeResponse(dto: ResponseDto): boolean {
        return dto.getResponseCode() === 204;
    }
    
    // ...
    
}
```
</TabItem>
<TabItem value="php" label="PHP">

```php
// ...
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
// ...

final class GitHubApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{

    // ...

    public function getWebhookUnsubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        Webhook            $webhook,
    ): RequestDto
    {
        $request    = new ProcessDto();
        $parameters = array_filter($this->getWebhookSubscriptions(), function ($item) use ($webhook) {
            return $item->getName() === $webhook->getName();
        })[0]->getParameters();

        return $this->getRequestDto(
            $request,
            $applicationInstall,
            CurlManager::METHOD_DELETE,
            sprintf(
                '/repos/%s/%s/hooks/%s',
                $parameters['owner'] ?? '',
                $parameters['record'] ?? '',
                $webhook->getId(),
            ),
        );
    }

    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        return $dto->getStatusCode() === 204;
    }

}

```

</TabItem>
</Tabs>


## Webhook Subscriptions
Finally, the `GetWebhookSubscriptions` method defines the webhooks that our application will allow to register.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript

export default class GitHubApplication extends ABasicApplication implements IWebhookApplication {
    
    // ...

    public getWebhookSubscriptions(): WebhookSubscription[] {
        return [
            new WebhookSubscription('issues', 'Webhook', '', { record: 'record', owner: 'owner' }),
            new WebhookSubscription('pull-request', 'Webhook', '', { record: 'record', owner: 'owner' }),
        ];
    }
    
    // ...
    
}
```
</TabItem>
<TabItem value="php" label="PHP">

```php
// ...

final class GitHubApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{
    // ...
    
    public function getWebhookSubscriptions(): array
    {
        return [
            new WebhookSubscription('issues', 'Webhook', '', ['record' => 'record', 'owner' => 'owner']),
            new WebhookSubscription('pull-request', 'Webhook', '', ['record' => 'record', 'owner' => 'owner']),
        ];
    }
    
    // ...
}
```
</TabItem>
</Tabs>


## Full application code


<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
import ApplicationTypeEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/ApplicationTypeEnum';
import CoreFormsEnum, { getFormName } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import { IWebhookApplication } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/IWebhookApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Webhook from '@orchesty/nodejs-sdk/dist/lib/Application/Database/Webhook';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import WebhookSubscription from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Webhook/WebhookSubscription';
import { ABasicApplication, TOKEN } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import ResponseDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/ResponseDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'git-hub';

export default class GitHubApplication extends ABasicApplication implements IWebhookApplication {

    public getApplicationType(): ApplicationTypeEnum {
      return ApplicationTypeEnum.WEBHOOK;
    }

    public getName(): string {
      return NAME;
    }

    public getPublicName(): string {
      return 'GitHub';
    }

    public getDescription(): string {
      return 'Service that helps developers store and manage their code, as well as track and control changes to their code';
    }

    public getFormStack(): FormStack {
      const form = new Form(CoreFormsEnum.AUTHORIZATION_FORM, getFormName(CoreFormsEnum.AUTHORIZATION_FORM))
        .addField(new Field(FieldType.TEXT, TOKEN, ' Token', undefined, true));

      return new FormStack().addForm(form);
    }

    public isAuthorized(applicationInstall: ApplicationInstall): boolean {
      const authorizationForm = applicationInstall.getSettings()[CoreFormsEnum.AUTHORIZATION_FORM];
      return authorizationForm?.[TOKEN];
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

    public getWebhookSubscribeRequestDto(
      applicationInstall: ApplicationInstall,
      subscription: WebhookSubscription,
      url: string,
    ): RequestDto {
      const request = new ProcessDto();
      const { owner, record } = subscription.getParameters();
      return this.getRequestDto(
        request,
        applicationInstall,
        HttpMethods.POST,
        `/repos/${owner}/${record}/hooks`,
        {
          config: {
            url,
            content_type: 'json',
          },
          name: 'web',
          events: [subscription.getName()],
        },
      );
    }

    public getWebhookSubscriptions(): WebhookSubscription[] {
      return [
        new WebhookSubscription('issues', 'Webhook', '', { record: 'record', owner: 'owner' }),
        new WebhookSubscription('pull-request', 'Webhook', '', { record: 'record', owner: 'owner' }),
      ];
    }

    public getWebhookUnsubscribeRequestDto(applicationInstall: ApplicationInstall, webhook: Webhook): RequestDto {
      const webhookSubscription = this.getWebhookSubscriptions().find(
        (item) => item.getName() === webhook.getName(),
      );
      if (!webhookSubscription) {
        throw new Error(`Webhook with name [${webhook.getName()}] has not been found.`);
      }

      const { record, owner } = webhookSubscription.getParameters();

      const request = new ProcessDto();
      return this.getRequestDto(
        request,
        applicationInstall,
        HttpMethods.DELETE,
        `/repos/${owner}/${record}/hooks/${webhook.getWebhookId()}`,
      );
    }

    public processWebhookSubscribeResponse(
      dto: ResponseDto,
      applicationInstall: ApplicationInstall,
    ): string {
      if (dto.getResponseCode() !== 201) {
        throw new Error((dto.getJsonBody() as { message: string }).message);
      }

      return (dto.getJsonBody() as { id: string }).id;
    }

    public processWebhookUnsubscribeResponse(dto: ResponseDto): boolean {
      return dto.getResponseCode() === 204;
    }

}

```
</TabItem>
<TabItem value="php" label="PHP">

```php
namespace Pipes\PhpSdk\Application;

use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\Utils\String\Json;

final class GitHubApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{

    public const NAME = 'git-hub';
    
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK->value;
    }

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

    public function getWebhookSubscriptions(): array
    {
        return [
            new WebhookSubscription('issues', 'Webhook', '', ['record' => 'record', 'owner' => 'owner']),
            new WebhookSubscription('pull-request', 'Webhook', '', ['record' => 'record', 'owner' => 'owner']),
        ];
    }

    public function getWebhookSubscribeRequestDto(
        ApplicationInstall  $applicationInstall,
        WebhookSubscription $subscription,
        string              $url,
    ): RequestDto
    {
        $request    = new ProcessDto();
        $parameters = $subscription->getParameters();

        return $this->getRequestDto(
            $request,
            $applicationInstall,
            CurlManager::METHOD_POST,
            sprintf('/repos/%s/%s/hooks', $parameters['owner'] ?? '', $parameters['record'] ?? ''),
            Json::encode(
                [
                    'config' => [
                        'url'          => $url,
                        'content_type' => 'json',
                    ],
                    'name'   => 'web',
                    'events' => [$subscription->getName()],
                ],
            ),
        );
    }

    public function getWebhookUnsubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        Webhook            $webhook,
    ): RequestDto
    {
        $request    = new ProcessDto();
        $parameters = array_filter($this->getWebhookSubscriptions(), function ($item) use ($webhook) {
            return $item->getName() === $webhook->getName();
        })[0]->getParameters();

        return $this->getRequestDto(
            $request,
            $applicationInstall,
            CurlManager::METHOD_DELETE,
            sprintf(
                '/repos/%s/%s/hooks/%s',
                $parameters['owner'] ?? '',
                $parameters['record'] ?? '',
                $webhook->getId(),
            ),
        );
    }

    public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string
    {
        $install;
        
        if ($dto->getStatusCode() !== 201) {
            throw new Exception($dto->getJsonBody()['message']);
        }

        return $dto->getJsonBody()['id'] ?? '';
    }

    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        return $dto->getStatusCode() === 204;
    }

}

```
</TabItem>
</Tabs>

## Application registration

Since we have only extended a previously developed application, we do not need to register it. If you don't already have a HubSpot application registered in the container, see the [Basic application](../tutorials/basic-application) tutorial.

## Creating a topology




![Webhooks topology](/img/tutorial/webhooks-topology.svg "Webhooks topology")

