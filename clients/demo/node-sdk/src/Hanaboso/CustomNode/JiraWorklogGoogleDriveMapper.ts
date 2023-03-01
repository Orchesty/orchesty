import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IWorklogDataMinimalWithIssue as IInput } from '../Batch/JiraSortWorklogsByProjectsBatch';
import AJiraWorklogGoogleDriveMapper from './AJiraWorklogGoogleDriveMapper';

export const NAME = 'jira-worklog-google-drive-mapper';

export default class JiraWorklogGoogleDriveMapper extends AJiraWorklogGoogleDriveMapper {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput[]>): ProcessDto<IOutput> {
        const data = dto.getJsonData();

        let result = 'started,worklog id,issue id,time spent,author,key,name,labels,comment\n';
        const projectKey = data?.[0].key.split('-')?.[0] ?? '';
        const date = data?.[0].date;
        const name = `${projectKey} | ${date?.from} - ${date?.to}`;

        data.forEach((item) => {
            let row = `${this.convertDateTimeToString(item.started)},${item.worklogId},${item.issueId},${this.convertSecondsToString(item.timeSpentSeconds)}`;
            row = `${row},${item.author},${item.key},${item.name ?? ''},${item.labels.join(';')},${item.comment ?? ''}\n`;
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
