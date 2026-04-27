import BeeceptorApplicationBase from '@orchesty/connector-beeceptor/dist//BeeceptorApplication';
import ApplicationTypeEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/ApplicationTypeEnum';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Webhook from '@orchesty/nodejs-sdk/dist/lib/Application/Database/Webhook';
import WebhookSubscription from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Webhook/WebhookSubscription';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import ResponseDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/ResponseDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { StatusCodes } from 'http-status-codes';

export enum WebhookType {
    PRODUCT_CREATED = 'product:created',
    PRODUCT_UPDATED = 'product:updated',
    CATEGORY_CREATED = 'category:created',
    CATEGORY_UPDATED = 'category:updated',
    PRODUCT_CATEGORY_UPDATED = 'productCategory:all',
}

export default class BeeceptorApplication extends BeeceptorApplicationBase {

    public getApplicationType(): ApplicationTypeEnum {
        return ApplicationTypeEnum.WEBHOOK;
    }

    public getWebhookSubscribeRequestDto(
        applicationInstall: ApplicationInstall,
        subscription: WebhookSubscription,
        url: string,
    ): RequestDto {
        return this.getRequestDto(
            new ProcessDto(),
            applicationInstall,
            HttpMethods.POST,
            '/api/webhooks',
            { event: subscription.getName(), url },
        );
    }

    public processWebhookSubscribeResponse(dto: ResponseDto<{ id: string }>): string {
        return dto.getJsonBody().id;
    }

    public getWebhookUnsubscribeRequestDto(
        applicationInstall: ApplicationInstall,
        webhook: Webhook,
    ): RequestDto {
        return this.getRequestDto(
            new ProcessDto(),
            applicationInstall,
            HttpMethods.DELETE,
            `/api/webhooks/${webhook.getWebhookId()}`,
        );
    }

    public processWebhookUnsubscribeResponse(dto: ResponseDto): boolean {
        return dto.getResponseCode() === StatusCodes.NO_CONTENT;
    }

    public getWebhookSubscriptions(): WebhookSubscription[] {
        return [
            new WebhookSubscription(WebhookType.CATEGORY_CREATED, 'category-created'),
            new WebhookSubscription(WebhookType.CATEGORY_UPDATED, 'category-updated'),
            new WebhookSubscription(WebhookType.PRODUCT_CREATED, 'product-created'),
            new WebhookSubscription(WebhookType.PRODUCT_UPDATED, 'product-updated'),
            new WebhookSubscription(WebhookType.PRODUCT_CATEGORY_UPDATED, 'product-category-updated'),
        ];
    }

    public syncListWebhookEvents(): { name: string; parameters: Record<string, string>; description: string }[] {
        return this.getWebhookSubscriptions().map((subscription) => ({
            name: subscription.getName(),
            parameters: subscription.getParameters(),
            description: subscription.getDescription(),
        }));
    }

}
