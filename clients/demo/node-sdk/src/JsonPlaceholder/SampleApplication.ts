import ApplicationTypeEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/ApplicationTypeEnum';
import CoreFormsEnum, { getFormName } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import { IWebhookApplication } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/IWebhookApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Webhook from '@orchesty/nodejs-sdk/dist/lib/Application/Database/Webhook';
import CustomAction from '@orchesty/nodejs-sdk/dist/lib/Application/Model/CustomAction/CustomAction';
import CustomActionType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/CustomAction/CustomActionType';
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

export default class SampleApplication extends ABasicApplication implements IWebhookApplication {

  protected infoFilename = `${__dirname}/info.html`;

  public getApplicationType(): ApplicationTypeEnum {
    return ApplicationTypeEnum.WEBHOOK;
  }

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
    data?: unknown,
  ): RequestDto {
    return new RequestDto(url ?? '', method, dto, data ?? '');
  }

  public getCustomActions(): CustomAction[] {
    return [
      new CustomAction('Go to Google', CustomActionType.OPEN, { url: 'https://google.com' }),
      new CustomAction('Run Sync', CustomActionType.CALL, { topologyName: 'system-events', nodeName: 'start' }),
      new CustomAction('Run Sync With body', CustomActionType.CALL, { topologyName: 'system-events', nodeName: 'start', body: '{}' }),
    ];
  }

  public getFormStack(): FormStack {
    const form = new Form(CoreFormsEnum.AUTHORIZATION_FORM, getFormName(CoreFormsEnum.AUTHORIZATION_FORM))
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

    const readOnlyForm = new Form('read_only_form_test', 'Readonly form')
      .addField(new Field(FieldType.TEXT, 'pin', 'PIN', 123).setReadOnly(true))
      .addField(new Field(FieldType.NUMBER, 'count', 'Counter', 666).setReadOnly(true))
      .setReadOnly(true);

    return new FormStack().addForm(form).addForm(readOnlyForm);
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
    webhook: Webhook,
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
