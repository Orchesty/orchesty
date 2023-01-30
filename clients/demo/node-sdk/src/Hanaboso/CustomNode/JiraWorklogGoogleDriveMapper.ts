import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IWorklogDataMinimalWithIssue as IInput } from '../Batch/JiraSortWorklogsByProjectsBatch';

export const NAME = 'jira-worklog-google-drive-mapper';

export default class JiraWorklogGoogleDriveMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput[]>): ProcessDto<IOutput> {
        const data = dto.getJsonData();

        let result = 'worklog id,issue id,time spent,author,key\n';
        const projectKey = data?.[0].key.split('-')?.[0] ?? '';
        const date = data?.[0].date;
        const name = `${projectKey} | ${date?.from} - ${date?.to}`;

        data.forEach((item) => {
            let row = `${item.worklogId},${item.issueId},${item.timeSpent}`;
            row = `${row},${item.author},${item.key}\n`;
            result = `${result}${row}`;
        });

        return dto.setNewJsonData<IOutput>({
            name,
            dataGrid: result,
        });
    }

}

interface IOutput {
    name: string;
    dataGrid: string;
}
