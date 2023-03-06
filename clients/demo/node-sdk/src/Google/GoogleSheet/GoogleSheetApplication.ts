import BaseGoogleSheetApplication from '@orchesty/nodejs-connectors/dist/lib/Google/GoogleSheet/GoogleSheetApplication';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import { SPREADSHEET_ID } from '../../Hanaboso/Connector/GoogleSheetGetSpreadsheet';
import { DIRECTORY_ID } from '../../Hanaboso/CustomNode/SetupGoogleSheetSettingDirectory';

export const DIRECTORY_SETTINGS = 'directory_settings';

export default class GoogleSheetApplication extends BaseGoogleSheetApplication {

    public getFormStack(): FormStack {
        return super.getFormStack().addForm(
            new Form(DIRECTORY_SETTINGS, 'Settings')
                .addField(new Field(FieldType.TEXT, SPREADSHEET_ID, 'Spreadsheet ID for reports', null, true))
                .addField(new Field(FieldType.TEXT, DIRECTORY_ID, 'Directory ID for per project reports', null, true)),
        );
    }

}
