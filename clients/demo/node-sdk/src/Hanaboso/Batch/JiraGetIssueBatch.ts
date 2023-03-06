import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import DataStorageManager from '@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { CORRELATION_ID } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { IEtl } from './JiraGetWorklogsBatch';

export const JIRA_GET_ISSUE_ENDPOINT = 'rest/api/3/issue';

export const NAME = 'jira-get-issue-batch';

export default class JiraGetIssueBatch extends ABatchNode {

    public constructor(private readonly dataStorageManager: DataStorageManager) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: BatchProcessDto): Promise<BatchProcessDto> {
        const appInstall = await this.getApplicationInstallFromProcess(dto);

        const pointer = Number(dto.getBatchCursor('0'));

        const worklogEtl = await this.dataStorageManager.load<IEtl<IWorklogDataMinimal>>(
            dto.getHeader(CORRELATION_ID) ?? '',
        );

        const worklogData = worklogEtl?.[0].getData()?.data;
        const id = worklogData?.[pointer]?.issueId;

        if (!id) {
            dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Connector is missing required data: "id".');
            return dto;
        }

        const request = await this.getApplication().getRequestDto(
            dto,
            appInstall,
            HttpMethods.GET,
            `${JIRA_GET_ISSUE_ENDPOINT}/${id}`,
        );
        const response = await this.getSender().send<IResponse>(request);
        const responseBody = response.getJsonBody();

        await this.dataStorageManager.remove(
            dto.getHeader(CORRELATION_ID) ?? '',
        );

        Object.assign(worklogData[pointer], {
            key: responseBody.key,
            name: responseBody.fields.customfield_10500?.[0].name,
            labels: responseBody.fields.labels,
            issueName: responseBody.fields.summary,
        });

        await this.dataStorageManager.store(
            dto.getHeader(CORRELATION_ID) ?? '',
            [{ data: worklogData, date: worklogEtl?.[0].getData()?.date }],
        );

        if (worklogData?.length && worklogData.length - 1 > pointer) {
            dto.setBatchCursor((pointer + 1).toString(), true);
        } else {
            dto.addItem({ success: 'ok' });
        }

        return dto;
    }

}

export interface IWorklogDataMinimal {
    started: string;
    worklogId: number;
    issueId: number;
    timeSpentSeconds: number;
    author: string;
}

/* eslint-disable @typescript-eslint/naming-convention */
export interface IResponse {
    key: string;
    fields: {
        summary: string;
        labels: string[];
        customfield_10500?: {
            name: string;
        }[];
    };
}
/* eslint-enable @typescript-eslint/naming-convention */
