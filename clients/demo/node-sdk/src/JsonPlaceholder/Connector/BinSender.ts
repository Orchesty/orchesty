import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';

export default class BinSender extends AConnector {

  public getName(): string {
    return 'bin-sender';
  }

  public async processAction(dto: ProcessDto): Promise<ProcessDto> {
    if (Math.random() > 0.9) {
      dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'to trash');

      return dto;
    }

    const request = new RequestDto('https://google.com/', HttpMethods.POST, dto, dto.getData());
    await this.getSender().send(request);

    return dto;
  }

}
