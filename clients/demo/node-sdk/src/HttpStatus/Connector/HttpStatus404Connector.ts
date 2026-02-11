import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { NAME as HTTP_STATUS_NAME } from '../HttpStatusApplication';

export const NAME = `${HTTP_STATUS_NAME}-404-connector`;

export default class HttpStatus404Connector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        await this.getSender().send(
            await this.getApplication().getRequestDto(
                dto,
                await this.getApplicationInstallFromProcess(dto),
                HttpMethods.GET,
                '404',
            ),
            { stopAndFail: '>=300' },
        );

        return dto;
    }

}
