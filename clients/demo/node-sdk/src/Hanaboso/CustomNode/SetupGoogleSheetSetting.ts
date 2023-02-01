import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { SPREADSHEET_ID } from '../Connector/GoogleSheetGetSpreadsheet';

export const NAME = 'setup-google-sheet-setting';

export default class SetupGoogleSheetSetting extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
        const body = dto.getJsonData();
        dto.addHeader(SPREADSHEET_ID, body.spredsheetId);
        dto.setNewJsonData<IOutput>(body);

        return dto;
    }

}

export interface IInput {
    from: string;
    to: string;
    spredsheetId: string;
}

export interface IOutput {
    from: string;
    to: string;
}
