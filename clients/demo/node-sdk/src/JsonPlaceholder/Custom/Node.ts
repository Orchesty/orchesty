import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export default class Node extends ACommonNode {

    public getName(): string {
        return 'node';
    }

    public processAction(dto: ProcessDto): ProcessDto | Promise<ProcessDto> {
        return dto;
    }

}
