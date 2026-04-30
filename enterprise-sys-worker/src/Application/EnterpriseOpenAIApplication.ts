import OpenAIApplication from '@orchesty/connector-open-ai/dist/OpenAIApplication';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import OpenAITrace from '../Connector/OpenAITrace';

export default class EnterpriseOpenAIApplication extends OpenAIApplication {

    public constructor(private readonly openAITrace: OpenAITrace) {
        super();
    }

    public async syncTrace(req: Request): Promise<Record<string, unknown>> {
        const { system, messages, user, sdk } = JSON.parse(String(req.body)) as {
            system?: string;
            messages?: { role: string; content: string }[];
            user: string;
            sdk: string;
        };

        const processDto = ProcessDto
            .createForFormRequest(this.getName(), user, sdk, crypto.randomUUID())
            .setNewJsonData({ system: system ?? '', messages: messages ?? [] });

        return (await this.openAITrace.processAction(processDto)).getJsonData();
    }

}
