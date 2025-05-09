import { container } from '@orchesty/nodejs-sdk';
import { RESULT_CODE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import crypto from 'crypto';
import { ComparatorFilter, NAME as COMPARATOR_FILTER } from '../../src/custom_node/ComparatorFilter';
import { Comparator, HASH_ALG, IInput } from '../../src/service/comparator';
import RedisStorage from '../../src/storage/RedisStorage';

let tester: NodeTester;
let redisStorage: RedisStorage;
let node: ComparatorFilter;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
async function storeHash(data: any): Promise<void> {
    const hasher = crypto.createHash(HASH_ALG);
    hasher.update(JSON.stringify(data));
    const hash = hasher.digest('hex');

    const pipeline = redisStorage.getPipeline();
    const dataToStore = [String(data.id), hash];
    redisStorage.hmSet(pipeline, 'masterKey', dataToStore);
    await pipeline.exec();
}

describe('Tests for ComparatorFilter', () => {
    beforeAll(() => {
        tester = new NodeTester(container, __filename);
        redisStorage = container.get(RedisStorage);
        node = new ComparatorFilter(
            container.get(Comparator),
            redisStorage,
        );
    });

    it('process - ok', async () => {
        await tester.testCustomNode(COMPARATOR_FILTER);
    });

    it('single page', async () => {
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }, { id: 3 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
            },
        } as IInput);

        const result = await node.processAction(dto);
        const resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }, { id: 2 }, { id: 3 }],
            updated: [],
            deleted: [],
        });
    });

    it('single page - pass as array', async () => {
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }, { id: 3 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                passAsListOfExistingItems: true,
            },
        } as IInput);

        const result = await node.processAction(dto);
        const resultData = result.getJsonData();

        expect(resultData).toStrictEqual([{ id: 1 }, { id: 2 }, { id: 3 }]);
    });

    it('single page - skip comparison', async () => {
        await storeHash({ id: 1 });
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                passAsListOfExistingItems: true,
                skipComparison: true,
            },
        } as IInput);

        const result = await node.processAction(dto);
        const resultData = result.getJsonData();

        expect(resultData).toStrictEqual([{ id: 1 }]);
    });

    it('single page large', async () => {
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }, { id: 3 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
            },
        } as IInput);

        const result = await node.processAction(dto);
        const resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }, { id: 2 }, { id: 3 }],
            updated: [],
            deleted: [],
        });
    });

    it('single page exclude', async () => {
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1, exc: { innr: 1 }, a: 1 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                excludedFields: ['exc.innr'],
            },
        } as IInput);

        const result = await node.processAction(dto);
        const resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1, exc: { innr: 1 }, a: 1 }],
            updated: [],
            deleted: [],
        });

        // Second run
        dto.setNewJsonData({
            items: [{ id: 1, exc: { innr: 1 }, a: 1 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                excludedFields: ['exc.innr'],
                stopOnEmptyArray: true,
            },
        } as IInput);

        const result2 = await node.processAction(dto);
        const resultData2 = result2.getJsonData();

        expect(resultData2).toStrictEqual({});
        expect(result2.getHeader(RESULT_CODE)).toBe('1003');
    });

    it('single page with lock', async () => {
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                lock: true,
            },
        } as IInput);

        const result = await node.processAction(dto);
        const resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }],
            updated: [],
            deleted: [],
        });
    });

    it('single page locked', async () => {
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }, { id: 3 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                lock: true,
            },
        } as IInput);

        await redisStorage.lock('masterKey');
        const result = await node.processAction(dto);
        const resultCode = result.getHeader(RESULT_CODE, '0');

        expect(resultCode).toStrictEqual(ResultCode.REPEAT.toString());
    });

    it('single page with same (ignored)', async () => {
        await storeHash({ id: 2 });

        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
            },
        } as IInput);

        const result = await node.processAction(dto);
        const resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }],
            updated: [],
            deleted: [],
        });
    });

    it('single page with update & delete', async () => {
        await storeHash({ id: 2, asd: 123 });
        await storeHash({ id: 3 });

        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                deleted: true,
                isLast: true,
            },
        } as IInput);

        const result = await node.processAction(dto);
        const resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }],
            updated: [{ id: 2 }],
            deleted: ['3'],
        });
    });

    it('buffered pages - creates', async () => {
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                isBuffered: true,
                isLast: false,
                totalCount: 4,
            },
        } as IInput);

        let result = await node.processAction(dto);
        let resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }, { id: 2 }],
            updated: [],
            deleted: [],
        });

        // Second (last) page
        dto.setNewJsonData({
            items: [{ id: 3 }, { id: 4 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                isBuffered: true,
                isLast: true,
                totalCount: 4,
            },
        } as IInput);

        result = await node.processAction(dto);
        resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 3 }, { id: 4 }],
            updated: [],
            deleted: [],
        });
    });

    it('buffered pages - update & delete', async () => {
        await storeHash({ id: 2, a: 1 });
        await storeHash({ id: 5 });
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                deleted: true,
                isLast: false,
                totalCount: 4,
            },
        } as IInput);

        let result = await node.processAction(dto);
        let resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }],
            updated: [{ id: 2 }],
            deleted: [],
        });

        // Second (last) page
        dto.setNewJsonData({
            items: [{ id: 3 }, { id: 4 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                deleted: true,
                isLast: true,
                totalCount: 4,
            },
        } as IInput);

        result = await node.processAction(dto);
        resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 3 }, { id: 4 }],
            updated: [],
            deleted: ['5'],
        });
    });

    it('buffered pages - out of order', async () => {
        const dto = new ProcessDto<IInput>();
        // Second (last) page
        dto.setNewJsonData({
            items: [{ id: 3 }, { id: 4 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                isBuffered: true,
                isLast: true,
                totalCount: 4,
            },
        } as IInput);

        let result = await node.processAction(dto);
        let resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 3 }, { id: 4 }],
            updated: [],
            deleted: [],
        });

        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                isBuffered: true,
                isLast: false,
                totalCount: 4,
            },
        } as IInput);

        result = await node.processAction(dto);
        resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }, { id: 2 }],
            updated: [],
            deleted: [],
        });
    });

    it('buffered pages - out of order & deleted', async () => {
        await storeHash({ id: 3 });
        const dto = new ProcessDto<IInput>();
        // Second (last) page
        dto.setNewJsonData({
            items: [{ id: 4 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                deleted: true,
                isLast: true,
                totalCount: 3,
            },
        } as IInput);

        let result = await node.processAction(dto);
        let resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 4 }],
            updated: [],
            deleted: [],
        });

        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                deleted: true,
                isLast: false,
                totalCount: 3,
            },
        } as IInput);

        result = await node.processAction(dto);
        resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }, { id: 2 }],
            updated: [],
            deleted: ['3'],
        });
    });

    it('buffered pages - but no end config has been provided', async () => {
        await storeHash({ id: 3 });
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 4 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                deleted: true,
            },
        } as IInput);

        let result = await node.processAction(dto);
        let resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 4 }],
            updated: [],
            deleted: [],
        });

        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                deleted: true,
            },
        } as IInput);

        result = await node.processAction(dto);
        resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }, { id: 2 }],
            updated: [],
            deleted: [],
        });
    });
});
