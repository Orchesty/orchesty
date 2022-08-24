import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import { ABasicApplication } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import { BodyInit } from 'node-fetch';

export const PIN = 'pin';

export default class TenantApplication extends ABasicApplication {

    public getDescription(): string {
        return 'Tenenant application description';
    }

    public getName(): string {
        return 'tenant';
    }

    public getPublicName(): string {
        return 'Tenant Application';
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
        const settingsForm = new Form(AUTHORIZATION_FORM, 'Settings');
        const tokenField = new Field(FieldType.TEXT, PIN, 'PIN').setReadOnly(true);

        settingsForm.addField(tokenField);
        return new FormStack().addForm(settingsForm);
    }

}
