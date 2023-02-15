import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { validate } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import Joi from 'joi';
import { ComparatorHashRepository } from '../service/storage/repository';

export const NAME = 'invalidate';

const schema = Joi.object({
    masterKey: Joi.string().required(),
    externalId: Joi.string().optional(),
}).required();

export class ComparatorInvalidate extends ACommonNode {

    public constructor(private readonly hashRepository: ComparatorHashRepository) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    @validate(schema)
    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IInput>> {
        await this.hashRepository.delete(dto.getJsonData());

        return dto;
    }

}

export interface IInput {
    masterKey: string;
    externalId?: string;
}
