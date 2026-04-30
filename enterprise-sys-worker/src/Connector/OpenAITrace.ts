import OpenAIPostResponseConnector, { getOutputText } from '@orchesty/connector-open-ai/dist/Connector/OpenAIPostResponseConnector';
import { NAME as OPEN_AI_NAME } from '@orchesty/connector-open-ai/dist/OpenAIApplication';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = `${OPEN_AI_NAME}-trace-connector`;

interface ITraceInput {
    messages: { role: string; content: string }[];
    system?: string;
}

export default class OpenAITrace extends OpenAIPostResponseConnector {

    public getName(): string {
        return NAME;
    }

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    public async processAction(dto: ProcessDto<any>): Promise<ProcessDto<any>> {
        const { system, messages } = dto.getJsonData() as ITraceInput;

        const body: Record<string, unknown> = {
            model: 'gpt-5.4-mini',
            input: messages.map(({ role, content }) => ({ role, content })),
        };

        if (system) {
            body.instructions = system;
        }

        dto.setJsonData(body);

        return dto.setNewJsonData({
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            response: getOutputText((await super.processAction(dto)).getJsonData() as any),
        });
    }

}
