import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import HttpMethods from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';

export default class BinSender extends AConnector {
  public getName = (): string => 'bin-sender';

  public async processAction(dto: ProcessDto): Promise<ProcessDto> {
    if (Math.random() > 0.9) {
      dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'to trash');

      return dto;
    }

    const request = new RequestDto('https://google.com/', HttpMethods.POST, dto, dto.data);
    await this._sender.send(request);

    return dto;
  }
}
