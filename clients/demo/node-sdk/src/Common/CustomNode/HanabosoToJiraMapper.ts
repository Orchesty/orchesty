import {
  IJiraIssue as IOutput,
  IssueTypeEnum,
} from '@orchesty/nodejs-connectors/dist/lib/Jira/Connector/JiraCreateIssueConnector';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { getPageName, PageEnum } from '../Enum/PageEnum';
import { ISales as IInput } from '../Interface/ISales';

export default class HanabosoToJiraMapper extends ACommonNode {

  public constructor(
        private readonly page: PageEnum,
        private readonly labels: string[],
  ) {
    super();
  }

  public getName(): string {
    return `hanaboso-to-${getPageName(this.page)}-jira-mapper`;
  }

  public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
    const {
      company,
      phone,
      email,
      language,
      message,
      applinth,
      course,
      aaas,
      team,
      support,
      subscribed,
      ...res
    } = dto.getJsonData();

    const hostedOrchesty = res['hosted-orchesty'];

    const interestedIn: string[] = [];

    if (hostedOrchesty) {
      interestedIn.push('Hosted Orchesty');
    }
    if (applinth) {
      interestedIn.push('Applinth');
    }
    if (aaas) {
      interestedIn.push('Applinth as a Service');
    }
    if (team) {
      interestedIn.push('Implementation team');
    }
    if (support) {
      interestedIn.push('Consultations or team support');
    }
    if (course) {
      interestedIn.push('Training course');
    }

    let summary = `${res['first-name']} ${res['last-name']}`;
    if (company) {
      summary = `${company} - ${summary}`;
    }

    let description = `Jméno: ${res['first-name']}
            Přijmení: ${res['last-name']}
            Email: ${email}
            Telefon: ${phone ?? ''}
            Preferovaný jazyk: ${language}
            Zpráva: ${message}`;

    if (interestedIn.length) {
      description = `${description} 
            Má zájem o: ${interestedIn.join(',')}`;
    }

    const data = {
      summary,
      description,
      labels: this.labels,
      issueType: IssueTypeEnum.TASK,
      projectKey: 'SAL',
      subscribed,
    };

    return dto.setNewJsonData(data);
  }

}
