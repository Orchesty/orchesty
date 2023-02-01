import JiraApplication from '@orchesty/nodejs-connectors/dist/lib/Jira/JiraApplication';
import CoreFormsEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import DataStorageManager from '@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { CORRELATION_ID } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';

export const JIRA_GET_UPDATED_WORKLOG_IDS_ENDPOINT = '/rest/api/3/worklog/updated';

export const NAME = 'jira-get-updated-worklog-ids-batch';

const HOST_URL = 'prefix_url';

export default class JiraGetUpdatedWorklogIdsBatch extends ABatchNode {

    public constructor(private readonly dataStorageManager: DataStorageManager) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: BatchProcessDto<IInput>): Promise<BatchProcessDto<IInput>> {
        const appInstall = await this.getApplicationInstallFromProcess(dto);

        const { from, to } = dto.getJsonData();
        const dateFrom = new Date(from);
        dateFrom.setHours(0, 0, 0);

        const dateTo = new Date(to);
        dateTo.setHours(23, 59, 59);

        const url = `${JIRA_GET_UPDATED_WORKLOG_IDS_ENDPOINT}?since=${dateFrom.getTime()}`;
        const nextUrl = dto.getBatchCursor(url);
        const request = await this.getApplication().getRequestDto(dto, appInstall, HttpMethods.GET, nextUrl);
        const response = await this.getSender().send<IResponse>(request);

        const responseData = response.getJsonBody();
        const worklogIds = responseData.values
            .filter((item) => item.updatedTime <= dateTo.getTime())
            .map((item) => item.worklogId);

        await this.dataStorageManager.store(
            dto.getHeader(CORRELATION_ID) ?? '',
            [{
                worklogIds,
                date: { from, to },
            } as IEtlWorklogIds],
        );

        if (!responseData.lastPage || responseData.until < dateTo.getTime()) {
            const baseUrl = appInstall.getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM]?.[HOST_URL];
            const nextPageUrl = responseData.nextPage.replace(baseUrl, '');
            dto.setBatchCursor(nextPageUrl, true);
        }

        if (responseData.lastPage) {
            dto.addItem({ status: 'success' });
        }

        return dto;
    }

}

export interface IInput {
    from: string;
    to: string;
}

export interface IEtlWorklogIds {
    worklogIds: number[];
    date: IInput;
}

interface IResponse {
    lastPage: boolean;
    self: string;
    since: number;
    until: number;
    values: Value[];
    nextPage: string;
}

interface Value {
    properties: [];
    updatedTime: number;
    worklogId: number;
}
