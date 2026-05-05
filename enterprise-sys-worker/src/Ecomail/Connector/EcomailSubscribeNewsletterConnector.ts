import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import EcomailApplication, { NEWSLETTER_LIST_ID, SETTINGS_FORM } from '../EcomailApplication';

export const NAME = 'ecomail-subscribe-newsletter';

export interface IInput {
    email: string;
}

/* eslint-disable @typescript-eslint/naming-convention */
export interface IOutput {
    id: number;
    email: string;
    name: string | null;
    surname: string | null;
    inserted_at: string;
    already_subscribed?: boolean;
}

interface ISubscribeRequestBody {
    subscriber_data: { email: string };
    skip_confirmation: boolean;
}
/* eslint-enable @typescript-eslint/naming-convention */

export default class EcomailSubscribeNewsletterConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const input = dto.getJsonData();
        checkParams(input as unknown as Record<string, unknown>, ['email']);

        const app = this.getApplication<EcomailApplication>();
        const appInstall = await this.getApplicationInstallFromProcess(dto);

        const listId = appInstall.getSettings()?.[SETTINGS_FORM]?.[NEWSLETTER_LIST_ID];
        if (listId === undefined || listId === null || listId === '') {
            dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                `Missing application setting [${SETTINGS_FORM}.${NEWSLETTER_LIST_ID}] — configure the Ecomail newsletter list ID first.`,
            );
            return dto as unknown as ProcessDto<IOutput>;
        }

        /* eslint-disable @typescript-eslint/naming-convention */
        const body: ISubscribeRequestBody = {
            subscriber_data: { email: input.email },
            skip_confirmation: false,
        };
        /* eslint-enable @typescript-eslint/naming-convention */

        const request = app.getRequestDto(
            dto,
            appInstall,
            HttpMethods.POST,
            `/lists/${listId}/subscribe`,
            body,
        );

        const response = await this.getSender().send(request, {
            success: [200, 201],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        return dto.setNewJsonData<IOutput>(response.getJsonBody() as IOutput);
    }

}
