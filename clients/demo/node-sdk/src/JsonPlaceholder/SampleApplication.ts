import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import { IWebhookApplication } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/IWebhookApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import WebhookSubscription from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Webhook/WebhookSubscription';
import { ABasicApplication } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import ResponseDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/ResponseDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { BodyInit } from 'node-fetch';

export default class SampleApplication extends ABasicApplication implements IWebhookApplication {

    protected isInstallable = false;

    public getDescription(): string {
        return 'Sample application description';
    }

    public getName(): string {
        return 'sample';
    }

    public getPublicName(): string {
        return 'SampleApp';
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public isAuthorized(applicationInstall: ApplicationInstall): boolean {
        return true;
    }

    public getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        url?: string,
        data?: BodyInit,
    ): RequestDto {
        return new RequestDto(url ?? '', method, dto, data ?? '');
    }

    public getFormStack(): FormStack {
        const form = new Form(AUTHORIZATION_FORM, 'Authorization settings')
            .addField(new Field(FieldType.TEXT, 'user', 'User'))
            .addField(new Field(FieldType.PASSWORD, 'pass', 'Password'));

        return new FormStack().addForm(form);
    }

    public getWebhookSubscribeRequestDto(
        applicationInstall: ApplicationInstall,
        subscription: WebhookSubscription,
        url: string,
    ): RequestDto {
        return new RequestDto(url, HttpMethods.POST, new ProcessDto(), '');
    }

    public getWebhookSubscriptions(): WebhookSubscription[] {
        return [
            new WebhookSubscription('webhook', 'start', 'topo'),
        ];
    }

    public getWebhookUnsubscribeRequestDto(
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        applicationInstall: ApplicationInstall,
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        id: string,
    ): RequestDto {
        return new RequestDto('', HttpMethods.POST, new ProcessDto());
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public processWebhookSubscribeResponse(dto: ResponseDto, applicationInstall: ApplicationInstall): string {
        return '';
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public processWebhookUnsubscribeResponse(dto: ResponseDto): boolean {
        return false;
    }

}
