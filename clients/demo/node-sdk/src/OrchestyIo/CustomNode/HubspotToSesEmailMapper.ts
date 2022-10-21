import { IInput as IOutput } from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/SimpleEmailService/Connector/SESSendEmail';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { readFileSync } from 'fs';
import { getOrchestyPageName, OrchestyPageEnum } from '../Enum/OrchestyPageEnum';

export default class HubspotToSesTransactionEmailMapper extends ACommonNode {

    public constructor(private readonly orchestyPage: OrchestyPageEnum) {
        super();
    }

    public getName(): string {
        return `hubspot-to-ses-${getOrchestyPageName(this.orchestyPage)}-email-mapper`;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IInput | IOutput> {
        const { emails } = dto.getJsonData();
        if (!emails.length) {
            dto.setStopProcess(
                ResultCode.DO_NOT_CONTINUE,
                `Required data not received: [emails:${emails}]`,
            );
            return dto;
        }

        const content = readFileSync(`${__dirname}/Templates/${getOrchestyPageName(this.orchestyPage)}.html`).toString();

        return dto.setNewJsonData<IOutput>({
            /* eslint-disable @typescript-eslint/naming-convention */
            Destination: {
                ToAddresses: emails,
            },
            Source: 'info@orchesty.io',
            Message: {
                Subject: {
                    Data: 'Confirmation email',
                },
                Body: {
                    Html: {
                        Data: content,
                    },
                },
            },
            /* eslint-disable @typescript-eslint/naming-convention */
        });
    }

}

export interface IInput {
    updated: number[];
    emails: string[];
}
