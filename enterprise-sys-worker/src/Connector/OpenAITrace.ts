import OpenAIPostResponseConnector, { getOutputText } from '@orchesty/connector-open-ai/dist/Connector/OpenAIPostResponseConnector';
import { NAME as OPEN_AI_NAME } from '@orchesty/connector-open-ai/dist/OpenAIApplication';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = `${OPEN_AI_NAME}-trace-connector`;

export default class OpenAITrace extends OpenAIPostResponseConnector {

    public getName(): string {
        return NAME;
    }

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    public async processAction(dto: ProcessDto<any>): Promise<ProcessDto<any>> {
        const { request } = dto.getJsonData() as IInput;

        dto.setJsonData({
            model: 'gpt-5.4-mini',
            input: [{ role: 'user', content: [{ type: 'input_text', text: request }] }],
        });

        return dto.setNewJsonData({
            response: getOutputText((await super.processAction(dto)).getJsonData() as any),
        });
    }
}

interface IInput {
    request: string;
}
