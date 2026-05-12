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

export const NAME = 'jsm';

export const EMAIL = 'email';
export const API_TOKEN = 'api_token';
export const BASE_URL_KEY = 'base_url';

export const SUPPORT_FORM = 'support';
export const SERVICE_DESK_ID = 'service_desk_id';
export const REQUEST_TYPE_BUG = 'request_type_bug';
export const REQUEST_TYPE_QUESTION = 'request_type_question';
export const REQUEST_TYPE_BILLING = 'request_type_billing';
export const REQUEST_TYPE_FEATURE_REQUEST = 'request_type_feature_request';
export const REQUEST_TYPE_OTHER = 'request_type_other';

export type SupportCategory = 'bug' | 'question' | 'billing' | 'feature_request' | 'other';

const REQUEST_TYPE_FIELD_BY_CATEGORY: Record<SupportCategory, string> = {
    bug: REQUEST_TYPE_BUG,
    question: REQUEST_TYPE_QUESTION,
    billing: REQUEST_TYPE_BILLING,
    /* eslint-disable @typescript-eslint/naming-convention */
    feature_request: REQUEST_TYPE_FEATURE_REQUEST,
    /* eslint-enable @typescript-eslint/naming-convention */
    other: REQUEST_TYPE_OTHER,
};

export default class JsmApplication extends ABasicApplication {

    public getName(): string {
        return NAME;
    }

    public getPublicName(): string {
        return 'Jira Service Management';
    }

    public getDescription(): string {
        return 'Atlassian Jira Service Management — creates customer requests on a Service Desk and uploads attachments via the temporary file API.';
    }

    public getLogo(): string {
        return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZmlsbD0iIzI2ODRGRiIgZD0iTTExLjU3MSAxMS41MTNIMC4wMDFhNS41MjEgNS41MjEgMCAwIDAgNS41MjEgNS41MjJoMi4xMjlWMTkuNDFhNS41MjMgNS41MjMgMCAwIDAgNS41MjIgNS41MjFWMTIuNDg0YTAuOTcxIDAuOTcxIDAgMCAwLTAuOTcxLTAuOTcxYzAgMC0wLjYzMSAwLTAuNjMxIDB6Ii8+PHBhdGggZmlsbD0iIzI2ODRGRiIgZD0iTTE3LjMxOSA1Ljc3SDUuNzVjMCAxLjQ2NS41ODEgMi44NjkgMS42MTYgMy45MDQgYTUuNTIxIDUuNTIxIDAgMCAwIDMuOTA0IDEuNjE3aDIuMTI4VjEzLjY2YTUuNTIzIDUuNTIzIDAgMCAwIDUuNTIyIDUuNTIxVjYuNzM5YTAuOTcxIDAuOTcxIDAgMCAwLTAuOTcxLTAuOTd6Ii8+PHBhdGggZmlsbD0iIzI2ODRGRiIgZD0iTTIzLjA0OCAwSDExLjQ4Yy0wLjAwMSAxLjQ2NS41ODEgMi44NjkgMS42MTYgMy45MDRhNS41MjEgNS41MjEgMCAwIDAgMy45MDMgMS42MThoMi4xMjlWNy44OTJhNS41MjQgNS41MjQgMCAwIDAgNS41MjEgNS41MjFWMC45N0EwLjk3MSAwLjk3MSAwIDAgMCAyMy4wNDggMHoiLz48L3N2Zz4=';
    }

    public getFormStack(): FormStack {
        const authForm = new Form(CoreFormsEnum.AUTHORIZATION_FORM, getFormName(CoreFormsEnum.AUTHORIZATION_FORM))
            .addField(new Field(FieldType.TEXT, EMAIL, 'Atlassian account e-mail', null, true))
            .addField(new Field(FieldType.TEXT, API_TOKEN, 'API token', null, true))
            .addField(new Field(
                FieldType.TEXT,
                BASE_URL_KEY,
                'Atlassian instance base URL (e.g. https://orchesty-solutions.atlassian.net)',
                null,
                true,
            ));

        const supportForm = new Form(SUPPORT_FORM, 'Support — request type mapping')
            .addField(new Field(FieldType.NUMBER, SERVICE_DESK_ID, 'Service Desk ID', null, true))
            .addField(new Field(FieldType.NUMBER, REQUEST_TYPE_BUG, 'Request type ID — Bug', null, true))
            .addField(new Field(FieldType.NUMBER, REQUEST_TYPE_QUESTION, 'Request type ID — Question', null, true))
            .addField(new Field(FieldType.NUMBER, REQUEST_TYPE_BILLING, 'Request type ID — Billing', null, true))
            .addField(new Field(
                FieldType.NUMBER,
                REQUEST_TYPE_FEATURE_REQUEST,
                'Request type ID — Feature request',
                null,
                true,
            ))
            .addField(new Field(FieldType.NUMBER, REQUEST_TYPE_OTHER, 'Request type ID — Other', null, true));

        return new FormStack().addForm(authForm).addForm(supportForm);
    }

    public isAuthorized(applicationInstall: ApplicationInstall): boolean {
        const authForm = applicationInstall.getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM];
        return !!authForm?.[EMAIL] && !!authForm?.[API_TOKEN] && !!authForm?.[BASE_URL_KEY];
    }

    public getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        url?: string,
        data?: unknown,
    ): RequestDto {
        const authForm = applicationInstall
            .getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM] as Record<string, unknown> | undefined;
        const email = authForm?.[EMAIL] as string;
        const token = authForm?.[API_TOKEN] as string;
        const basic = Buffer.from(`${email}:${token}`).toString('base64');

        return new RequestDto(url ?? '', method, dto, data, {
            [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
            [CommonHeaders.ACCEPT]: JSON_TYPE,
            [CommonHeaders.AUTHORIZATION]: `Basic ${basic}`,
        });
    }

    public getBaseUrl(applicationInstall: ApplicationInstall): string {
        const authForm = applicationInstall
            .getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM] as Record<string, unknown> | undefined;
        const baseUrl = (authForm?.[BASE_URL_KEY] as string | undefined)?.trim();
        if (!baseUrl) {
            throw new Error(`JSM application install is missing [${BASE_URL_KEY}] in the authorization form.`);
        }
        return baseUrl.replace(/\/+$/, '');
    }

    public getServiceDeskId(applicationInstall: ApplicationInstall): number {
        const support = applicationInstall.getSettings()?.[SUPPORT_FORM] as Record<string, unknown> | undefined;
        const raw = support?.[SERVICE_DESK_ID];
        const id = Number(raw);
        if (!Number.isFinite(id) || id <= 0) {
            throw new Error(`JSM application install is missing [${SUPPORT_FORM}.${SERVICE_DESK_ID}].`);
        }
        return id;
    }

    public getRequestTypeIdForCategory(
        applicationInstall: ApplicationInstall,
        category: SupportCategory,
    ): number {
        const support = applicationInstall.getSettings()?.[SUPPORT_FORM] as Record<string, unknown> | undefined;
        const fieldKey = REQUEST_TYPE_FIELD_BY_CATEGORY[category];
        const raw = support?.[fieldKey];
        const id = Number(raw);
        if (!Number.isFinite(id) || id <= 0) {
            throw new Error(
                `JSM application install is missing [${SUPPORT_FORM}.${fieldKey}] for category "${category}".`,
            );
        }
        return id;
    }

}
