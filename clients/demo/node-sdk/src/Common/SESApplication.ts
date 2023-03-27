import { SESClient } from '@aws-sdk/client-ses';
import {
    CREDENTIALS,
    ENDPOINT,
    LATEST,
    REGION,
    REGIONS,
    VERSION,
} from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/AAwsApplication';
import Base from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/SimpleEmailService/SESApplication';
import CoreFormsEnum, { getFormName } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import { ses } from '../Config/Config';

export default class SESApplication extends Base {

    public getFormStack(): FormStack {
        const form = new Form(CoreFormsEnum.AUTHORIZATION_FORM, getFormName(CoreFormsEnum.AUTHORIZATION_FORM))
            .addField(new Field(FieldType.SELECT_BOX, REGION, 'Region', undefined, true).setChoices(REGIONS))
            .addField(new Field(FieldType.TEXT, ENDPOINT, 'Custom Endpoint'));

        return new FormStack().addForm(form);
    }

    public isAuthorized(applicationInstall: ApplicationInstall): boolean {
        const authorizationForm = applicationInstall.getSettings()[CoreFormsEnum.AUTHORIZATION_FORM];
        return authorizationForm?.[REGION];
    }

    public getSESClient(applicationInstall: ApplicationInstall): SESClient {
        const settings = applicationInstall.getSettings()[CoreFormsEnum.AUTHORIZATION_FORM];

        return new SESClient(
            {
                [CREDENTIALS]: {
                    accessKeyId: ses.key,
                    secretAccessKey: ses.secret,
                },
                [REGION]: settings[REGION],
                [VERSION]: LATEST,
                [ENDPOINT]: settings?.[ENDPOINT] ?? [],
            },
        );
    }

}
