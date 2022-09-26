import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import { ABasicApplication } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import { BodyInit } from 'node-fetch';

export default class NonInstallableApplication extends ABasicApplication {

    protected isInstallable = false;

    public getDescription(): string {
        return 'Non-installable application description';
    }

    public getName(): string {
        return 'non-installable';
    }

    public getPublicName(): string {
        return 'Non-installable Application';
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
        return new FormStack();
    }

}
