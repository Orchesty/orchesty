import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import DateTimeUtils from '@orchesty/nodejs-sdk/dist/lib/Utils/DateTimeUtils';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { DateTime } from 'luxon';
import { DIRECTORY_SETTINGS } from '../../Google/GoogleSheet/GoogleSheetApplication';
import { SPREADSHEET_ID } from '../Connector/GoogleSheetGetSpreadsheet';

export const NAME = 'setup-google-sheet-setting-spreadsheet';
export const DATE_FORMAT = 'yyyy-LL-dd';
export const LAST_TOPOLOGY_RUN = 'lastTopologyRun';

export default class SetupGoogleSheetSettingSpreadsheet extends ACommonNode {

  public getName(): string {
    return NAME;
  }

  public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
    const body = dto.getJsonData();
    const applicationInstall = await this.getApplicationInstallFromProcess(dto);

    body.spredsheetId ??= applicationInstall.getSettings()[DIRECTORY_SETTINGS]?.[SPREADSHEET_ID];
    body.from ??= applicationInstall.getNonEncryptedSettings()[LAST_TOPOLOGY_RUN]
            ?? DateTimeUtils.getFormattedDate(DateTime.utc().minus({ day: 1 }), DATE_FORMAT);
    body.to ??= DateTimeUtils.getFormattedDate(DateTime.utc(), DATE_FORMAT);

    return dto.addHeader(SPREADSHEET_ID, body.spredsheetId).setNewJsonData(body);
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
