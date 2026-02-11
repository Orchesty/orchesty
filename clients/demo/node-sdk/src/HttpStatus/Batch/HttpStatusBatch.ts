import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';

export const NAME = 'http-status-batch';

export default class HttpStatusBatch extends ABatchNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: BatchProcessDto<IInput>): BatchProcessDto | Promise<BatchProcessDto> {
        for (let i = 0; i < (dto.getJsonData().size ?? 100); i++) {
            dto.addItem({ counter: i }, dto.getUser());
        }

        return dto;
    }

}

export interface IInput {
    size?: number;
}
