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
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';

export const CLOUD_URL = 'url';
export const CLOUD_API_KEY = 'apiKey';

export default class CloudCallbackApplication extends ABasicApplication {

    public getName(): string {
        return 'cloud-callback';
    }

    public getPublicName(): string {
        return 'Cloud Callback';
    }

    public getDescription(): string {
        return 'Cloud backend callback for invoice confirmation';
    }

    public getLogo(): string {
        return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZmlsbD0iIzRGQzNGNyIgZD0iTTE5LjM1IDEwLjA0QzE4LjY3IDYuNTkgMTUuNjQgNCAxMiA0IDkuMTEgNCA2LjYgNS42NCA1LjM1IDguMDQgMi4zNCA4LjM2IDAgMTAuOTEgMCAxNGMwIDMuMzEgMi42OSA2IDYgNmgxM2MyLjc2IDAgNS0yLjI0IDUtNSAwLTIuNjQtMi4wNS00Ljc4LTQuNjUtNC45NnoiLz48L3N2Zz4=';
    }

    // ── Form ──

    public getFormStack(): FormStack {
        const form = new Form(
            CoreFormsEnum.AUTHORIZATION_FORM,
            getFormName(CoreFormsEnum.AUTHORIZATION_FORM),
        )
            .addField(new Field(FieldType.TEXT, CLOUD_URL, 'Callback Base URL', null, true))
            .addField(new Field(FieldType.TEXT, CLOUD_API_KEY, 'X-Api-Key', null, true));

        return new FormStack().addForm(form);
    }

    public isAuthorized(applicationInstall: ApplicationInstall): boolean {
        const form = applicationInstall.getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM];
        return !!form?.[CLOUD_URL] && !!form?.[CLOUD_API_KEY];
    }

    // ── Request building ──

    public getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: string,
        url?: string,
        data?: unknown,
    ): RequestDto {
        const settings = applicationInstall.getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM];
        const baseUrl = settings?.[CLOUD_URL] as string;
        const apiKey = settings?.[CLOUD_API_KEY] as string;

        return new RequestDto(
            url ?? baseUrl,
            method as HttpMethods,
            dto,
            data,
            {
                [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
                [CommonHeaders.ACCEPT]: JSON_TYPE,
                // eslint-disable-next-line @typescript-eslint/naming-convention
                'X-Api-Key': apiKey,
            },
        );
    }

}
