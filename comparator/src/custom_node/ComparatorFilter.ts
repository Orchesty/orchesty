import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import OnRepeatException from '@orchesty/nodejs-sdk/dist/lib/Exception/OnRepeatException';
import { CORRELATION_ID } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { validate } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import Joi from 'joi';
import {
    Comparator,
    IConfiguration,
    IInput as IComparatorInput,
    IOutput as IComparatorOutput,
} from '../service/comparator';
import RedisStorage from '../storage/RedisStorage';

export const NAME = 'comparator';

const schema = Joi.object({
    items: Joi.array().required(),
    configuration: Joi.object({
        idField: Joi.string().required(),
        masterKey: Joi.string().required(),
        excludedFields: Joi.array().items(Joi.string()).optional().allow(null),
        stopOnEmptyArray: Joi.boolean().optional().allow(null),
        ttl: Joi.number().optional().allow(null),
        totalCount: Joi.number().optional().allow(null),
        passAsListOfExistingItems: Joi.boolean().optional().allow(null).default(false),
        skipComparison: Joi.boolean().optional().allow(null),
        lock: Joi.boolean().optional().allow(null),
        deleted: Joi.boolean().optional().allow(null),
        isLast: Joi.boolean().optional().allow(null),
    }).required(),
});

export class ComparatorFilter extends ACommonNode {

    public constructor(
        private readonly comparator: Comparator,
        private readonly redis: RedisStorage,
    ) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    @validate(schema)
    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        try {
            const correlationId = dto.getHeader(CORRELATION_ID) ?? '';
            const input = dto.getJsonData();
            dto.setNewJsonData({}); // clear message body

            if (this.isLockable(input.configuration)) {
                try {
                    await this.redis.lock(input.configuration.masterKey);
                } catch (e: unknown) {
                    return dto.setRepeater(2 * 60, 5, (e as Error).message) as unknown as ProcessDto<IOutput>;
                }
            }

            const output = await this.getOutput(input, correlationId);
            output.deleted = await this.getDeletedItems(input.configuration, correlationId);

            if (input.configuration.stopOnEmptyArray) {
                const allLen = output.created.length + output.updated.length + output.deleted.length;
                if (allLen === 0) {
                    return dto.setStopProcess(ResultCode.DO_NOT_CONTINUE, 'Empty comparator result') as unknown as ProcessDto<IOutput>;
                }
            }

            if (input.configuration.passAsListOfExistingItems) {
                dto.setNewJsonData([...output.created, ...output.updated]);
            } else {
                dto.setNewJsonData(output);
            }

            if (this.isLockable(input.configuration)) {
                await this.redis.unlock(input.configuration.masterKey);
            }

            return dto as unknown as ProcessDto<IOutput>;
        } catch (e: unknown) {
            throw new OnRepeatException(60, 20, (e as { message ?: string }).message);
        }
    }

    private async getOutput(input: IInput, correlationId: string): Promise<IOutput> {
        if (input.configuration.skipComparison === true) {
            const output = this.comparator.getEmptyOutput();
            input.items.forEach((it) => {
                output.updated.push(it);
            });

            return output;
        }

        return this.comparator.compare(input, correlationId);
    }

    private isLockable(config: IConfiguration): boolean {
        return !config.skipComparison && config.lock === true;
    }

    private async getDeletedItems(config: IConfiguration, correlationId: string): Promise<string[]> {
        if (config.deleted === true) {
            return this.comparator.getDeletedItems(config, correlationId);
        }

        return [];
    }

}

export type IInput = IComparatorInput;
export type IOutput = IComparatorOutput;
