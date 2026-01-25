import { IInput as IOutput } from '@orchesty/connector-amazon-apps-simple-email-service/dist/Connector/SESSendEmail';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import * as os from 'os';

export const NAME = 'hanaboso-contact-form-mapper';

export default class ContactFormMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IInput | IOutput> {
        const { email, message, name } = dto.getJsonData();

        if (!email || !message || !name) {
            dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                'Not all required informations were send.',
            );
            return dto;
        }

        return dto.setNewJsonData<IOutput>({
            /* eslint-disable @typescript-eslint/naming-convention */
            Destination: {
                ToAddresses: ['sales@hanaboso.com'],
            },
            Source: 'info@hanaboso.com',
            ReplyToAddresses: [email],
            Message: {
                Subject: {
                    Data: `Zpráva z kontaktní fomuláře: ${name}`,
                },
                Body: {
                    Text: {
                        Data: `Od: ${name}${os.EOL}Zpráva: ${message}`,
                    },
                },
            },

        });
    }

}

export interface IInput {
    name: string;
    email: string;
    message: string;
}
