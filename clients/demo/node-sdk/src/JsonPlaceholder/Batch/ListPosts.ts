import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import HttpMethods from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';

export default class ListPosts extends ABatchNode {
  public getName = (): string => 'list-posts';

  public async processAction(dto: BatchProcessDto): Promise<BatchProcessDto> {
    const request = new RequestDto('https://jsonplaceholder.typicode.com/posts', HttpMethods.GET, dto);
    const res = await this._sender.send(request);

    (res.jsonBody as IResponse[]).forEach((item) => {
      dto.addItem(item, dto.user);
    });

    return dto;
  }
}

interface IResponse {
  userId: number,
  id: number,
  title: string,
  body: string,
}
