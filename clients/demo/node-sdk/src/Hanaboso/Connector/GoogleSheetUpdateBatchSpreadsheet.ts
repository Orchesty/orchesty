import GoogleSheetApplication from '@orchesty/connector-google-sheet/dist/GoogleSheetApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import DataStorageManager from '@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import DateTimeUtils from '@orchesty/nodejs-sdk/dist/lib/Utils/DateTimeUtils';
import { CORRELATION_ID } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { DateTime } from 'luxon';
import { DATE_FORMAT, LAST_TOPOLOGY_RUN } from '../CustomNode/SetupGoogleSheetSettingSpreadsheet';
import { IResponse as ISpreadsheet } from './GoogleSheetGetSpreadsheet';

export const NAME = 'google-sheet-update-batch-spredsheet';

export const SPREADSHEET_ID = 'spreadsheet_id';

const GOOGLE_SHEET_GET_SPREADSHEET = '/v4/spreadsheets';

export default class GoogleSheetUpdateBatchSpreadsheet extends AConnector {

    public constructor(private readonly dataStorageManager: DataStorageManager) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const app = this.getApplication<GoogleSheetApplication>();
        const spredsheetId = dto.getHeader(SPREADSHEET_ID);
        const spredsheetCacheKey = `${spredsheetId}-${dto.getHeader(CORRELATION_ID)}`;

        if (!spredsheetId) {
            dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Connector is missing required Header: "spredsheetId".');
            return dto;
        }

        const sheetEtl = await this.dataStorageManager.load<ISpreadsheet>(
            spredsheetCacheKey,
        );

        const sheet = sheetEtl?.[0].getData()?.sheets[0];

        if (!sheet) {
            dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Connector is missing required data: "sheet".');
            return dto;
        }

        const body = {
            requests: [
                {
                    updateCells: {
                        start: {
                            columnIndex: 0,
                            rowIndex: 0,
                            sheetId: sheet.properties.sheetId,
                        },
                        rows: sheet.data[0].rowData,
                        fields: '*',
                    },
                },
            ],
        };

        const applicationInstall = await this.getApplicationInstallFromProcess(dto);

        const req = await app.getRequestDto(
            dto,
            applicationInstall,
            HttpMethods.POST,
            `${GOOGLE_SHEET_GET_SPREADSHEET}/${spredsheetId}:batchUpdate`,
            body,
        );

        await this.getSender().send<IResponse>(req, [200]);

        await this.dataStorageManager.remove(
            spredsheetCacheKey,
        );

        await this.writeLastTimeRun(applicationInstall);

        return dto.setJsonData({ success: 'ok' });
    }

    private async writeLastTimeRun(applicationInstall: ApplicationInstall): Promise<void> {
        applicationInstall.addNonEncryptedSettings({
            [LAST_TOPOLOGY_RUN]: DateTimeUtils.getFormattedDate(DateTime.utc(), DATE_FORMAT),
        });

        await this.getDbClient().getApplicationRepository().update(applicationInstall);
    }

}

export interface IResponse {
    spreadsheetId: string;
    properties: IProperties;
    spreadsheetUrl: string;
    sheets: ISheets[];
}

interface IProperties {
    title: string;
}

interface IPropertiesSheet extends IProperties {
    sheetId: number;
}

interface ISheets {
    properties: IPropertiesSheet;
    data: IData[];
}

interface IData {
    rowData: IRowData[];
}

interface IRowData {
    values: IValue[];
}

export interface IValue {
    userEnteredValue: IStringValue;
}

interface IStringValue {
    stringValue: string;
}

interface IInput {
    spreadsheetId: string;
}
