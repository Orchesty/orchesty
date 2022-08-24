import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';

export default class ListUsers extends ABatchNode {

    public getName(): string {
        return 'list-users';
    }

    public async processAction(dto: BatchProcessDto): Promise<BatchProcessDto> {
        const request = new RequestDto('https://jsonplaceholder.typicode.com/users', HttpMethods.GET, dto);
        const res = await this.getSender().send<IResponse[]>(request);

        res.getJsonBody().forEach((item) => {
            dto.addItem(item, dto.getUser());
        });

        return dto;
    }

}

interface IResponse {
    id: number;
    name: string;
    username: string;
    email: string;
    phone: string;
    website: string;
    address: {
        street: string;
        suite: string;
        city: string;
        zipcode: string;
        geo: {
            lat: string;
            lng: string;
        };
    };
    company: {
        name: string;
        catchPhrase: string;
        bs: string;
    };
}
