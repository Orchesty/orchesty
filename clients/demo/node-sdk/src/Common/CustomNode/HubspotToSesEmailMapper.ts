import { IInput as IOutput } from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/SimpleEmailService/Connector/SESSendEmail';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { readFileSync } from 'fs';
import { getPageName, PageEnum } from '../Enum/PageEnum';

export default class HubspotToSesTransactionEmailMapper extends ACommonNode {

  public constructor(protected readonly page: PageEnum) {
    super();
  }

  public getName(): string {
    return `hubspot-to-ses-${getPageName(this.page)}-email-mapper`;
  }

  public processAction(dto: ProcessDto<IInput>): ProcessDto<IInput | IOutput> {
    const { emails } = dto.getJsonData();
    if (!emails.length) {
      dto.setStopProcess(
        ResultCode.STOP_AND_FAILED,
        `Required data not received: [emails:${emails}]`,
      );
      return dto;
    }

    return dto.setNewJsonData<IOutput>({
      /* eslint-disable @typescript-eslint/naming-convention */
      Destination: {
        ToAddresses: emails,
      },
      Source: this.getSourceEmail(),
      Message: {
        Subject: {
          Data: 'Confirmation email',
        },
        Body: {
          Html: {
            Data: this.getContent(dto.getJsonData()),
          },
        },
      },
      /* eslint-disable @typescript-eslint/naming-convention */
    });
  }

  protected getSourceEmail(): string {
    return 'info@orchesty.io';
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected getContent(data: IInput): string {
    return readFileSync(`${__dirname}/Templates/${getPageName(this.page)}.html`).toString();
  }

}

export interface IInput {
    updated: number[];
    emails: string[];
    language?: string;
}
