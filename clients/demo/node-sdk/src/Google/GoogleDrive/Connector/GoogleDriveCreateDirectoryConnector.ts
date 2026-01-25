import BaseGoogleDriveCreateDirectoryConnector, {
    IInput,
    IOutput,
} from '@orchesty/connector-google-drive/dist/Connector/GoogleDriveCreateDirectoryConnector';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { DIRECTORY_ID } from '../../../Hanaboso/CustomNode/SetupGoogleSheetSettingDirectory';

export default class GoogleDriveCreateDirectoryConnector extends BaseGoogleDriveCreateDirectoryConnector {

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const baseData = dto.getJsonData();
        const { id, name } = (await super.processAction(dto)).getJsonData();

        return dto.addHeader(DIRECTORY_ID, id).setNewJsonData({
            ...baseData,
            name,
            id,
        });
    }

}
