import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export default class GetPost extends AConnector {

    public getName(): string {
        return 'get-post';
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        return dto.setNewJsonData(
            (await this.getSender().send<IResponse>(
                new RequestDto(
                    `https://jsonplaceholder.typicode.com/posts/${dto.getJsonData().id}`,
                    HttpMethods.GET,
                    dto,
                ),
            )).getJsonBody(),
        );
    }

}

interface IInput {
    id: string;
}

interface IResponse {
    userId: number;
    id: number;
    title: string;
    body: string;
}

type IOutput = IResponse;
