import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import { CORRELATION_ID } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { validate } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import Joi from 'joi';
import { ComparatorBuffer } from '../model';
import {
    Comparator,
    emptyOutput,
    IConfiguration,
    IInput as IComparatorInput,
    IOutput as IComparatorOutput,
} from '../service/comparator';
import { ComparatorBufferRepository, ComparatorLockRepository } from '../service/storage/repository';

export const NAME = 'filter';

const schema = Joi.object({
    items: Joi.array().required(),
    configuration: Joi.object({
        idField: Joi.string().required(),
        masterKey: Joi.string().required(),
        excludedFields: Joi.array().items(Joi.string()).optional().allow(null),
        stopOnEmptyArray: Joi.boolean().optional().allow(null),
        ttl: Joi.number().optional().allow(null),
        totalCount: Joi.number().optional().allow(null),
        isLast: Joi.boolean().optional().allow(null),
        isBuffered: Joi.boolean().optional().allow(null),
    }).required(),
});

export class ComparatorFilter extends ACommonNode {

    public constructor(
        private readonly comparator: Comparator,
        private readonly bufferRepository: ComparatorBufferRepository,
        private readonly lockRepository: ComparatorLockRepository,
    ) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    @validate(schema)
    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        let { items, configuration } = dto.getJsonData(); // eslint-disable-line
        dto.setNewJsonData({}); // clear message body

        if (configuration.isBuffered === true) {
            const buffer = await this.processPage(items, configuration, dto.getHeader(CORRELATION_ID, '') ?? '');
            if (!buffer) {
                return dto
                    .setNewJsonData(emptyOutput)
                    .setStopProcess(ResultCode.DO_NOT_CONTINUE, 'unclosed Comparator buffer');
            }

            items = buffer.data;
        }

        const acquired = await this.lockRepository.acquireLock(configuration.masterKey);
        if (!acquired) {
            return dto
                .setNewJsonData(emptyOutput)
                .setRepeater(2 * 60, 5, 'master_key locked for Comparator process');
        }

        const resultDto = this.processDataSet(items, configuration, dto);
        await this.lockRepository.unlock(configuration.masterKey);

        return resultDto;
    }

    private async processPage(
        items: Record<string, unknown>[],
        configuration: IConfiguration,
        correlationId: string,
    ): Promise<ComparatorBuffer | null> {
        const key = `${configuration.masterKey}_${correlationId}`;
        const buffer: ComparatorBuffer = {
            id: '',
            ttl: new Date(),
            pages: [],
            key,
            data: items,
            closed: configuration.isLast === true,
        };

        const info = await this.bufferRepository.upsertBuffer(buffer);
        if (info.closed && info.total >= (configuration.totalCount || 0)) {
            return this.bufferRepository.findOne({ key });
        }

        return null;
    }

    private async processDataSet(
        items: Record<string, unknown>[],
        configuration: IConfiguration,
        dto: ProcessDto,
    ): Promise<ProcessDto<IOutput>> {
        const changes = await this.comparator.compare({ items, configuration });
        if (configuration.stopOnEmptyArray
            && !changes.created.length
            && !changes.updated.length
            && !changes.deleted.length
        ) {
            return dto.setNewJsonData(emptyOutput).setStopProcess(ResultCode.DO_NOT_CONTINUE, 'empty Comparator result');
        }

        return dto.setNewJsonData(changes);
    }

}

export type IInput = IComparatorInput;
export type IOutput = IComparatorOutput;
