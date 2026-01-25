import BaseGoogleDriveUpdateFileConnector, {
    IInput as BaseIInput,
    IOutput,
} from '@orchesty/connector-google-drive/dist/Connector/GoogleDriveUpdateFileConnector';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { DIRECTORY_ID } from '../../../Hanaboso/CustomNode/SetupGoogleSheetSettingDirectory';

export default class GoogleDriveUpdateFileConnector extends BaseGoogleDriveUpdateFileConnector {

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        return super.processAction(dto.setNewJsonData({
            fileId: dto.getJsonData().spreadsheetId,
            parentId: dto.getHeader(DIRECTORY_ID),
        }));
    }

}

export interface IInput extends BaseIInput {
    spreadsheetId: string;
}
