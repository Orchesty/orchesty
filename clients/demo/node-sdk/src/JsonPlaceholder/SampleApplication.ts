import { ABasicApplication } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import HttpMethods from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { BodyInit } from 'node-fetch';
import { IWebhookApplication } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/IWebhookApplication';
import WebhookSubscription from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Webhook/WebhookSubscription';
import ResponseDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/ResponseDto';
import { AUTHORIZATION_FORM } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';

export default class SampleApplication extends ABasicApplication implements IWebhookApplication {
  public getDescription = (): string => 'Sample application description';

  public getName = (): string => 'sample';

  public getPublicName = (): string => 'SampleApp';

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  public isAuthorized = (applicationInstall: ApplicationInstall): boolean => true;

  // eslint-disable-next-line max-len
  public getLogo = (): string => 'data:image/svg+xml;base64, PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIzLjAuMiwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAxMDkuNSAxMjQuNSIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgMTA5LjUgMTI0LjU7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4KCS5zdDB7ZmlsbDojOTVCRjQ3O30KCS5zdDF7ZmlsbDojNUU4RTNFO30KCS5zdDJ7ZmlsbDojRkZGRkZGO30KPC9zdHlsZT4KPGc+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNOTUuOSwyMy45Yy0wLjEtMC42LTAuNi0xLTEuMS0xYy0wLjUsMC05LjMtMC4yLTkuMy0wLjJzLTcuNC03LjItOC4xLTcuOWMtMC43LTAuNy0yLjItMC41LTIuNy0wLjMKCQljMCwwLTEuNCwwLjQtMy43LDEuMWMtMC40LTEuMy0xLTIuOC0xLjgtNC40Yy0yLjYtNS02LjUtNy43LTExLjEtNy43YzAsMCwwLDAsMCwwYy0wLjMsMC0wLjYsMC0xLDAuMWMtMC4xLTAuMi0wLjMtMC4zLTAuNC0wLjUKCQljLTItMi4yLTQuNi0zLjItNy43LTMuMWMtNiwwLjItMTIsNC41LTE2LjgsMTIuMmMtMy40LDUuNC02LDEyLjItNi44LDE3LjVjLTYuOSwyLjEtMTEuNywzLjYtMTEuOCwzLjdjLTMuNSwxLjEtMy42LDEuMi00LDQuNQoJCWMtMC4zLDIuNS05LjUsNzMtOS41LDczbDc2LjQsMTMuMmwzMy4xLTguMkMxMDkuNSwxMTUuOCw5NiwyNC41LDk1LjksMjMuOXogTTY3LjIsMTYuOGMtMS44LDAuNS0zLjgsMS4yLTUuOSwxLjgKCQljMC0zLTAuNC03LjMtMS44LTEwLjlDNjQsOC42LDY2LjIsMTMuNyw2Ny4yLDE2Ljh6IE01Ny4yLDE5LjljLTQsMS4yLTguNCwyLjYtMTIuOCwzLjljMS4yLTQuNywzLjYtOS40LDYuNC0xMi41CgkJYzEuMS0xLjEsMi42LTIuNCw0LjMtMy4yQzU2LjksMTEuNiw1Ny4zLDE2LjUsNTcuMiwxOS45eiBNNDkuMSw0YzEuNCwwLDIuNiwwLjMsMy42LDAuOUM1MS4xLDUuOCw0OS41LDcsNDgsOC42CgkJYy0zLjgsNC4xLTYuNywxMC41LTcuOSwxNi42Yy0zLjYsMS4xLTcuMiwyLjItMTAuNSwzLjJDMzEuNywxOC44LDM5LjgsNC4zLDQ5LjEsNHoiLz4KCTxnPgoJCTxwYXRoIGNsYXNzPSJzdDEiIGQ9Ik05NC44LDIyLjljLTAuNSwwLTkuMy0wLjItOS4zLTAuMnMtNy40LTcuMi04LjEtNy45Yy0wLjMtMC4zLTAuNi0wLjQtMS0wLjVsMCwxMDkuN2wzMy4xLTguMgoJCQljMCwwLTEzLjUtOTEuMy0xMy42LTkyQzk1LjgsMjMuMyw5NS4zLDIyLjksOTQuOCwyMi45eiIvPgoJCTxwYXRoIGNsYXNzPSJzdDIiIGQ9Ik01OCwzOS45bC0zLjgsMTQuNGMwLDAtNC4zLTItOS40LTEuNmMtNy41LDAuNS03LjUsNS4yLTcuNSw2LjRjMC40LDYuNCwxNy4zLDcuOCwxOC4zLDIyLjkKCQkJYzAuNywxMS45LTYuMywyMC0xNi40LDIwLjZjLTEyLjIsMC44LTE4LjktNi40LTE4LjktNi40bDIuNi0xMWMwLDAsNi43LDUuMSwxMi4xLDQuN2MzLjUtMC4yLDQuOC0zLjEsNC43LTUuMQoJCQljLTAuNS04LjQtMTQuMy03LjktMTUuMi0yMS43Yy0wLjctMTEuNiw2LjktMjMuNCwyMy43LTI0LjRDNTQuNywzOC4yLDU4LDM5LjksNTgsMzkuOXoiLz4KCTwvZz4KPC9nPgo8L3N2Zz4K';

  public getRequestDto = (
    dto: AProcessDto,
    applicationInstall: ApplicationInstall,
    method: HttpMethods,
    url?: string,
    data?: BodyInit,
  ): RequestDto => new RequestDto(url ?? '', method, dto, data ?? '');

  public getFormStack = (): FormStack => {
    const form = new Form(AUTHORIZATION_FORM, 'Authorization settings')
      .addField(new Field(FieldType.TEXT, 'user', 'User'))
      .addField(new Field(FieldType.PASSWORD, 'pass', 'Password'));

    return new FormStack().addForm(form);
  };

  public getWebhookSubscribeRequestDto = (
    applicationInstall: ApplicationInstall,
    subscription: WebhookSubscription,
    url: string,
  ): RequestDto => new RequestDto(url, HttpMethods.POST, new ProcessDto(), '');

  public getWebhookSubscriptions = (): WebhookSubscription[] => [
    new WebhookSubscription('webhook', 'start', 'topo'),
  ];

  public getWebhookUnsubscribeRequestDto = (
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    applicationInstall: ApplicationInstall,
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    id: string,
  ): RequestDto => new RequestDto('', HttpMethods.POST, new ProcessDto());

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  public processWebhookSubscribeResponse = (dto: ResponseDto, applicationInstall: ApplicationInstall): string => '';

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  public processWebhookUnsubscribeResponse = (dto: ResponseDto): boolean => false;
}
