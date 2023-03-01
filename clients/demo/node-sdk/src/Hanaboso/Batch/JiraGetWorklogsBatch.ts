import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import DataStorageManager from '@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { CORRELATION_ID } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { IInput as IDate } from './JiraGetUpdatedWorklogIdsBatch';

export const JIRA_GET_WORKLOGS_LIST_ENDPOINT = '/rest/api/3/worklog/list';

export const NAME = 'jira-get-worklogs-batch';

const batchAmount = 1000;

export default class JiraGetWorklogsBatch extends ABatchNode {

    public constructor(private readonly dataStorageManager: DataStorageManager) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: BatchProcessDto): Promise<BatchProcessDto> {
        const appInstall = await this.getApplicationInstallFromProcess(dto);

        const worklogData = await this.dataStorageManager.load<IEtlWithIds>(
            dto.getHeader(CORRELATION_ID) ?? '',
        );

        const etlData = worklogData[0].getData();

        const worklogIds = etlData?.worklogIds;

        if (!etlData || !worklogIds) {
            dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Connector is missing required data: "worklogIds".');
            return dto;
        }

        const pointer = Number(dto.getBatchCursor('0'));
        const nextStep = pointer + batchAmount;
        const ids = worklogIds.slice(pointer, nextStep);

        const request = await this.getApplication().getRequestDto(
            dto,
            appInstall,
            HttpMethods.POST,
            JIRA_GET_WORKLOGS_LIST_ENDPOINT,
            { ids },
        );
        const response = await this.getSender().send<IOutput[]>(request);

        await this.dataStorageManager.remove(
            dto.getHeader(CORRELATION_ID) ?? '',
        );

        const stripResponse = response.getJsonBody().map((item) => ({
            started: item.started,
            worklogId: item.id,
            issueId: item.issueId,
            timeSpentSeconds: item.timeSpentSeconds,
            author: item.author.displayName,
            comment: item.comment?.content[0]?.content[0]?.text,
        }));

        const newWorklogCache = {
            worklogIds,
            data: [...etlData.data ?? [], ...stripResponse],
            date: etlData?.date,
        };

        await this.dataStorageManager.store(
            dto.getHeader(CORRELATION_ID) ?? '',
            [newWorklogCache],
        );

        if (worklogIds.slice(nextStep).length) {
            dto.setBatchCursor(nextStep.toString(), true);
        } else {
            return dto.addItem({ status: 'success' });
        }

        return dto;
    }

}

export interface IEtl<T> {
    date: IDate;
    data?: T[];
}

export interface IEtlWithIds extends IEtl<IOutput> {
    worklogIds: number[];
}

export interface IOutput {
    id: string;
    created: string;
    updated: string;
    author: Author;
    issueId: string;
    self: string;
    started: string;
    timeSpent: string;
    timeSpentSeconds: number;
    updateAuthor: Author;
    comment?: Comment;
}

export interface Author {
    accountId: string;
    accountType: string;
    active: boolean;
    avatarUrls: Record<string, string>;
    displayName: string;
    self: string;
    timeZone: string;
}

export interface Comment {
    version: number;
    type: string;
    content: {
        type: string;
        content: {
            type: string;
            text: string;
        }[];
    }[];
}
