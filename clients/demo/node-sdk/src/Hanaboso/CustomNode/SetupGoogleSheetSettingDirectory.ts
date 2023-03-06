import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import DateTimeUtils from '@orchesty/nodejs-sdk/dist/lib/Utils/DateTimeUtils';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { DateTime } from 'luxon';
import { DIRECTORY_SETTINGS } from '../../Google/GoogleSheet/GoogleSheetApplication';

export const NAME = 'setup-google-sheet-setting-directory';
export const DATE_FORMAT = 'yyyy-LL-dd';
export const DIRECTORY_ID = 'directory_id';

export default class SetupGoogleSheetSettingDirectory extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const body = dto.getJsonData();
        const applicationInstall = await this.getApplicationInstallFromProcess(dto);

        body.directoryId ??= applicationInstall.getSettings()[DIRECTORY_SETTINGS]?.[DIRECTORY_ID];
        body.from ??= DateTimeUtils.getFormattedDate(DateTime.utc().minus({ month: 1 }), DATE_FORMAT);
        body.to ??= DateTimeUtils.getFormattedDate(DateTime.utc(), DATE_FORMAT);

        return dto.setNewJsonData({
            parentId: body.directoryId,
            name: DateTimeUtils.getFormattedDate(DateTime.utc().minus({ month: 1 }), 'yyyy-LL'),
            from: body.from,
            to: body.to,
        });
    }

}

export interface IInput {
    from: string;
    to: string;
    directoryId: string;
}

export interface IOutput {
    from: string;
    to: string;
    name: string;
    parentId: string;
}
