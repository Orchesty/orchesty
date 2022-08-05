import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export default class Node extends ACommonNode {
  getName(): string {
    return 'node';
  }

  processAction(dto: ProcessDto): Promise<ProcessDto> | ProcessDto {
    return dto;
  }
}
