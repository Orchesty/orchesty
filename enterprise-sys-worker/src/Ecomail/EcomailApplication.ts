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

export const NAME = 'ecomail';
export const API_KEY = 'api_key';

export const SETTINGS_FORM = 'settings';
export const NEWSLETTER_LIST_ID = 'newsletter_list_id';
export const FROM_EMAIL = 'from_email';
export const FROM_NAME = 'from_name';
export const BUSINESS_EMAIL = 'business_email';
export const SALES_BUSINESS_TEMPLATE_ID = 'sales_business_template_id';
export const SALES_CUSTOMER_TEMPLATE_ID = 'sales_customer_template_id';

export const SYSTEM_FORM = 'system';
export const SYSTEM_FROM_EMAIL = 'system_from_email';
export const SYSTEM_FROM_NAME = 'system_from_name';

export const SUPPORT_FORM = 'support';
export const SUPPORT_FROM_EMAIL = 'support_from_email';
export const SUPPORT_FROM_NAME = 'support_from_name';

const BASE_URL = 'https://api2.ecomailapp.cz';

export default class EcomailApplication extends ABasicApplication {

    public getName(): string {
        return NAME;
    }

    public getPublicName(): string {
        return 'Ecomail';
    }

    public getDescription(): string {
        return 'Email marketing platform for newsletters, automations, and transactional emails.';
    }

    public getLogo(): string {
        return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZmlsbD0iIzc4NDhGNCIgZD0iTTIyIDZjMC0xLjEtLjktMi0yLTJINGMtMS4xIDAtMiAuOS0yIDJ2MTJjMCAxLjEuOSAyIDIgMmgxNmMxLjEgMCAyLS45IDItMlY2em0tMiAwbC04IDUtOC01aDE2em0wIDEySDRWOGw4IDUgOC01djEweiIvPjwvc3ZnPg==';
    }

    public getFormStack(): FormStack {
        const authForm = new Form(CoreFormsEnum.AUTHORIZATION_FORM, getFormName(CoreFormsEnum.AUTHORIZATION_FORM))
            .addField(new Field(FieldType.TEXT, API_KEY, 'API Key', null, true));

        const settingsForm = new Form(SETTINGS_FORM, 'Settings')
            .addField(new Field(FieldType.NUMBER, NEWSLETTER_LIST_ID, 'Newsletter list ID', null, true))
            .addField(new Field(FieldType.TEXT, FROM_EMAIL, 'Sender email (transactional)', null, true))
            .addField(new Field(FieldType.TEXT, FROM_NAME, 'Sender name (transactional)', null, true))
            .addField(new Field(FieldType.TEXT, BUSINESS_EMAIL, 'Business notification recipient email', null, true))
            .addField(new Field(FieldType.NUMBER, SALES_BUSINESS_TEMPLATE_ID, 'Sales — business notification template ID', null, true))
            .addField(new Field(FieldType.NUMBER, SALES_CUSTOMER_TEMPLATE_ID, 'Sales — customer confirmation template ID', null, true));

        const systemForm = new Form(SYSTEM_FORM, 'System emails')
            .addField(new Field(FieldType.TEXT, SYSTEM_FROM_EMAIL, 'System sender email', null, true))
            .addField(new Field(FieldType.TEXT, SYSTEM_FROM_NAME, 'System sender name', null, true));

        const supportForm = new Form(SUPPORT_FORM, 'Support emails')
            .addField(new Field(FieldType.TEXT, SUPPORT_FROM_EMAIL, 'Support sender email (e.g. support@orchesty-solutions.com)', null, true))
            .addField(new Field(FieldType.TEXT, SUPPORT_FROM_NAME, 'Support sender name', null, true));

        return new FormStack().addForm(authForm).addForm(settingsForm).addForm(systemForm).addForm(supportForm);
    }

    public isAuthorized(applicationInstall: ApplicationInstall): boolean {
        const authForm = applicationInstall.getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM];
        return !!authForm?.[API_KEY];
    }

    public getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        url?: string,
        data?: unknown,
    ): RequestDto {
        const apiKey = applicationInstall.getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM]?.[API_KEY] as string;

        return new RequestDto(`${BASE_URL}${url}`, method, dto, data, {
            [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
            [CommonHeaders.ACCEPT]: JSON_TYPE,
            key: apiKey,
        });
    }

}
