import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { NAME as ZAI_NAME } from '../Application/ZaiApplication';

export const NAME = `${ZAI_NAME}-trace-connector`;

export default class ZaiTrace extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const { request } = dto.getJsonData() as IInput;

        const requestDto = await this.getApplication().getRequestDto(
            dto,
            await this.getApplicationInstallFromProcess(dto),
            HttpMethods.POST,
            '/api/coding/paas/v4/chat/completions',
            {
                model: 'glm-5-turbo',
                messages: [{ role: 'user', content: request }],
                stream: false,
            },
        );

        const responseDto = await this.getSender().send<IGLMResponse>(requestDto, {
            success: 200,
            stopAndFail: [400],
        });

        return dto.setNewJsonData({ response: responseDto.getJsonBody().choices?.[0]?.message?.content ?? '' });
    }

}

interface IInput {
    request: string;
}

export interface IOutput {
    response: string;
}

interface IGLMResponse {
    choices?: { message?: { content?: string } }[];
}
