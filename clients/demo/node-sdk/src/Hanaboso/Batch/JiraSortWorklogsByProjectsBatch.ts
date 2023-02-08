import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import DataStorageManager from '@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { CORRELATION_ID } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import { IInput as IDate } from './JiraGetUpdatedWorklogIdsBatch';
import { IEtl } from './JiraGetWorklogsBatch';

export const NAME = 'jira-sort-worklogs-by-projects-batch';

export default class JiraSortWorklogsByProjectsBatch extends ABatchNode {

    public constructor(private readonly dataStorageManager: DataStorageManager) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: BatchProcessDto): Promise<BatchProcessDto> {
        const worklogData = await this.dataStorageManager.load<IEtl<IWorklogDataMinimalWithIssue>>(
            dto.getHeader(CORRELATION_ID) ?? '',
        );

        const result: IWorklogDataMinimalWithIssue[][] = [];
        const map = new Map<string, IWorklogDataMinimalWithIssue[]>();

        worklogData?.[0].getData()?.data?.forEach((_item) => {
            const item = _item;
            const key = this.getProjectKey(item.key);
            item.date = worklogData?.[0].getData()?.date;
            map.set(key, [...map.get(key) ?? [], item]);
        });

        map.forEach((array) => {
            result.push(array);
        });

        dto.setItemList(result);

        return dto;
    }

    private getProjectKey(key: string): string {
        return key.split('-')[0];
    }

}

export interface IWorklogDataMinimalWithIssue {
    worklogId: number;
    issueId: number;
    timeSpent: string;
    author: string;
    key: string;
    date?: IDate;
}
