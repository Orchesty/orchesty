import CoreFormsEnum, { getFormName } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import { ABasicApplication } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';

export const PIN = 'pin';

export default class TenantApplication extends ABasicApplication {

  protected isInstallable = false;

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
    data?: unknown,
  ): RequestDto {
    return new RequestDto(url ?? '', method, dto, data ?? '');
  }

  public getFormStack(): FormStack {
    const settingsForm = new Form(
      CoreFormsEnum.AUTHORIZATION_FORM,
      getFormName(CoreFormsEnum.AUTHORIZATION_FORM),
    ).setReadOnly(true);
    settingsForm.addField(new Field(FieldType.TEXT, PIN, 'PIN').setReadOnly(true));

    const form = new Form('Sample form', 'Sample settings')
      .setDescription('Some form description')

      .addField(new Field(FieldType.TEXT, 'name', 'Name'))
      .addField(new Field(FieldType.TEXT, 'disName', 'Disabled Name', 'default').setDisabled(true))
      .addField(new Field(FieldType.TEXT, 'readName', 'Read Name', 'default').setReadOnly(true))

      .addField(new Field(FieldType.URL, 'url', 'Url'))
      .addField(new Field(FieldType.URL, 'disUrl', 'Disabled Url', 'https://default.local').setDisabled(true))
      .addField(new Field(FieldType.URL, 'readUrl', 'Read Url', 'https://default.local').setReadOnly(true))

      .addField(new Field(FieldType.CHECKBOX, 'check', 'IsOk'))
      .addField(new Field(FieldType.CHECKBOX, 'disCheck', 'IsOk').setDisabled(true))
      .addField(new Field(FieldType.CHECKBOX, 'forced', 'Forced', true)
        .setReadOnly(true)
        .setDescription('Forced read-only field'))

      .addField(new Field(FieldType.NUMBER, 'number', 'Number'))
      .addField(new Field(FieldType.NUMBER, 'disNumber', 'Disabled Number').setDisabled(true))
      .addField(new Field(FieldType.NUMBER, 'readNumber', 'Read Number').setReadOnly(true))

      .addField(new Field(FieldType.PASSWORD, 'pass', 'Password'))
      .addField(new Field(FieldType.PASSWORD, 'pass2', 'Password2'))

      .addField(new Field(FieldType.MULTI_SELECT, 'multi', 'MultiSelect').setChoices([{ key: 'val' }, { foo: 'bar' }, { some: 'bbq' }]))
      .addField(new Field(FieldType.MULTI_SELECT, 'disMulti', 'Disabled MultiSelect', 'key')
        .setChoices([{ key: 'val' }])
        .setDescription('Some desc for multi-selectbox')
        .setDisabled(true))
      .addField(new Field(FieldType.MULTI_SELECT, 'readMulti', 'Read MultiSelect', ['key', 'some'])
        .setChoices([{ key: 'val' }, { foo: 'bar' }, { some: 'bbq' }])
        .setReadOnly(true))

      .addField(new Field(FieldType.SELECT_BOX, 'sel', 'Select').setChoices([{ key: 'val' }]))
      .addField(new Field(FieldType.SELECT_BOX, 'disSel', 'Disabled Select', 'key')
        .setChoices([{ key: 'val' }])
        .setDescription('Some desc for selectbox')
        .setDisabled(true))
      .addField(new Field(FieldType.SELECT_BOX, 'readSel', 'Read Select', 'key')
        .setChoices([{ key: 'val' }])
        .setReadOnly(true));

    return new FormStack().addForm(settingsForm).addForm(form);
  }

}
