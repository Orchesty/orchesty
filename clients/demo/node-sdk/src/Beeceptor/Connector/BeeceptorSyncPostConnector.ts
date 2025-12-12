import { NAME as BEECEPTOR_APP_NAME } from '@orchesty/nodejs-connectors/dist/lib/Beeceptor/BeeceptorApplication';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = `${BEECEPTOR_APP_NAME}-sync-post-connector`;

export default class BeeceptorSyncPostConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const request = await this.getApplication().getRequestDto(
            dto,
            await this.getApplicationInstallFromProcess(dto),
            HttpMethods.POST,
            '/api/posts',
            dto.getJsonData(),
        );

        await this.getSender().send(request);

        return dto;
    }

}
