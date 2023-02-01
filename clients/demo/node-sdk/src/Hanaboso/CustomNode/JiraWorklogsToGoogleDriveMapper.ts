import JiraApplication from '@orchesty/nodejs-connectors/dist/lib/Jira/JiraApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import DataStorageManager from '@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager';
import { CORRELATION_ID } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { IEtl } from '../Batch/JiraGetWorklogsBatch';
import {
    IWorklogDataMinimalWithIssue as IInput,
    IWorklogDataMinimalWithIssue,
} from '../Batch/JiraSortWorklogsByProjectsBatch';
import { IResponse as ISpreadsheet, IValue, SPREADSHEET_ID } from '../Connector/GoogleSheetGetSpreadsheet';

export const NAME = 'jira-worklogs-to-google-drive-mapper';

export default class JiraWorklogsToGoogleDriveMapper extends ACommonNode {

    public constructor(private readonly dataStorageManager: DataStorageManager) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput[]>): Promise<ProcessDto> {
        const spredsheetId = dto.getHeader(SPREADSHEET_ID);
        const spredsheetCacheKey = `${spredsheetId}-${dto.getHeader(CORRELATION_ID)}`;

        if (!spredsheetId) {
            dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Connector is missing required Header: "spredsheetId".');
            return dto;
        }

        const worklogEtl = await this.dataStorageManager.load<IEtl<IWorklogDataMinimalWithIssue>>(
            dto.getHeader(CORRELATION_ID) ?? '',
        );

        const sheetEtl = await this.dataStorageManager.load<ISpreadsheet>(spredsheetCacheKey);

        const worklogCache = worklogEtl?.[0].getData()?.data;
        const spreadsheet = sheetEtl?.[0].getData();
        let isEqual = false;

        worklogCache?.forEach((worklog) => {
            spreadsheet?.sheets?.[0].data.forEach((sheetData, i) => {
                sheetData.rowData.forEach((row, x) => {
                    if (row.values[0].userEnteredValue.stringValue === worklog.worklogId.toString()) {
                        spreadsheet.sheets[0].data[i].rowData[x].values = this.createRowValues(worklog);
                        isEqual = true;
                    }
                });
                if (!isEqual) {
                    spreadsheet.sheets[0].data[i].rowData.push({ values: this.createRowValues(worklog) });
                }
                isEqual = false;
            });
        });

        await this.dataStorageManager.remove(
            spredsheetCacheKey,
        );

        await this.dataStorageManager.store(
            spredsheetCacheKey,
            [spreadsheet],
        );

        return dto.setNewJsonData({ status: 'success' });
    }

    private createRowValues(row: IWorklogDataMinimalWithIssue): IValue[] {
        return [
            {
                userEnteredValue: {
                    stringValue: row.worklogId.toString(),
                },
            },
            {
                userEnteredValue: {
                    stringValue: row.issueId.toString(),
                },
            },
            {
                userEnteredValue: {
                    stringValue: row.timeSpent,
                },
            },
            {
                userEnteredValue: {
                    stringValue: row.author,
                },
            },
            {
                userEnteredValue: {
                    stringValue: row.key,
                },
            },
        ];
    }

}
