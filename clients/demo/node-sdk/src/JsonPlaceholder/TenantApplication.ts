import { ABasicApplication } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import HttpMethods from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import { BodyInit } from 'node-fetch';
import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';

export const PIN = 'pin';

export default class TenantApplication extends ABasicApplication {
  public getDescription = (): string => 'Tenenant application description';

  public getName = (): string => 'tenant';

  public getPublicName = (): string => 'Tenant Application';

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  public isAuthorized = (applicationInstall: ApplicationInstall): boolean => true;

  public getRequestDto = (
    dto: AProcessDto,
    applicationInstall: ApplicationInstall,
    method: HttpMethods,
    url?: string,
    data?: BodyInit,
  ): RequestDto => new RequestDto(url ?? '', method, dto, data ?? '');

  public getFormStack = (): FormStack => {
    const settingsForm = new Form(AUTHORIZATION_FORM, 'Settings');
    const tokenField = new Field(FieldType.TEXT, PIN, 'PIN').setReadOnly(true);

    settingsForm.addField(tokenField);
    return new FormStack().addForm(settingsForm);
  };
}
