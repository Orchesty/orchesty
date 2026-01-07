import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export default class HttpStatus401Connector extends AConnector {

    public getName(): string {
        return 'http-status-401-connector';
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        await this.getSender().send(
            new RequestDto('https://mock.httpstatus.io/401', HttpMethods.GET, dto),
            { stopAndFail: '>=300' },
        );

        return dto;
    }

}
