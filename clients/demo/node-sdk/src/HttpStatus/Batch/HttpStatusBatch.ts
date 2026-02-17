import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { NAME as HTTP_STATUS_NAME } from '../HttpStatusApplication';

export const NAME = `${HTTP_STATUS_NAME}-batch`;

export default class HttpStatusBatch extends ABatchNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: BatchProcessDto<IInput>): BatchProcessDto | Promise<BatchProcessDto> {
        for (let i = 0; i < (dto.getJsonData().size ?? 100); i++) {
            dto.addItem({ counter: i });
        }

        return dto;
    }

}

export interface IInput {
    size?: number;
}
