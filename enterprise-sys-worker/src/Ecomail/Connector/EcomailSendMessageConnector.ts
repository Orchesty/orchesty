import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import EcomailApplication from '../EcomailApplication';

export const NAME = 'ecomail-send-message';

interface IRecipient {
    email: string;
    name?: string;
    cc?: string;
    bcc?: string;
}

interface IMergeVar {
    name: string;
    content: string;
}

interface IAttachment {
    type: string;
    name: string;
    content: string;
}

export interface IInput {
    subject: string;
    from_name: string;
    from_email: string;
    to: IRecipient[];
    reply_to?: string;
    text?: string;
    html?: string;
    amp_html?: string;
    global_merge_vars?: IMergeVar[];
    attachments?: IAttachment[];
    options?: {
        click_tracking?: boolean;
        open_tracking?: boolean;
    };
}

export interface IOutput {
    total_rejected_recipients: number;
    total_accepted_recipients: number;
    id: number;
}

interface IApiResponse {
    results: IOutput;
}

export default class EcomailSendMessageConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const input = dto.getJsonData();
        checkParams(input as unknown as Record<string, unknown>, ['subject', 'from_name', 'from_email', 'to']);

        if (!input.html && !input.text) {
            throw new Error('Either [html] or [text] must be provided.');
        }

        const app = this.getApplication<EcomailApplication>();
        const appInstall = await this.getApplicationInstallFromProcess(dto);

        const request = app.getRequestDto(
            dto,
            appInstall,
            HttpMethods.POST,
            '/transactional/send-message',
            { message: input },
        );

        const response = await this.getSender().send(request, {
            success: [200],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        const body = response.getJsonBody() as IApiResponse;

        return dto.setNewJsonData<IOutput>(body.results);
    }

}
