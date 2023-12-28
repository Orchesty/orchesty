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
    const result = [['key', 'name', 'time', 'organisation', 'labels']];

    const projectKey = data?.[0].key.split('-')?.[0] ?? '';
    const date = data?.[0].date;
    const name = `${projectKey} | ${date?.from} - ${date?.to}`;

    const newData = new Map<string, IInput[]>();
    data.forEach((item) => {
      const innerData = newData.get(item.key);

      if (innerData) {
        newData.set(item.key, [...innerData, item]);
      } else {
        newData.set(item.key, [item]);
      }
    });

    newData.forEach((value) => {
      result.push([
        value[0].key,
        value[0].issueName,
        this.convertSecondsToString(
          value.reduce((seconds, { timeSpentSeconds }) => seconds + timeSpentSeconds, 0),
        ),
        value[0].name ?? '',
        value[0].labels.join(';'),
      ]);
    });

    return dto.setNewJsonData<IOutput>({
      name,
      data: result,
    });
  }

}

interface IOutput {
    name: string;
    data: string[][];
}
