import { container } from '@orchesty/nodejs-sdk';
import { RESULT_CODE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { ComparatorFilter, NAME as COMPARATOR_FILTER } from '../../src/custom_node/ComparatorFilter';
import { IInput } from '../../src/service/comparator';
import { ComparatorBufferRepository, ComparatorHashRepository, ComparatorLockRepository } from '../../src/service/storage/repository';
import { ComparatorMock } from './ComparatorMock';

let tester: NodeTester;

describe('Tests for ComparatorFilter', () => {
    let node: ComparatorFilter;

    beforeAll(() => {
        tester = new NodeTester(container, __filename);
        node = new ComparatorFilter(
            new ComparatorMock(container.get(ComparatorHashRepository)),
            container.get(ComparatorBufferRepository),
            container.get(ComparatorLockRepository),
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
            created: [{ id: 1 }],
            updated: [{ id: 2 }],
            deleted: [3],
        });
    });

    it('single page locked', async () => {
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }, { id: 3 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
            },
        } as IInput);

        await container.get(ComparatorLockRepository).acquireLock('masterKey');
        const result = await node.processAction(dto);
        const resultCode = result.getHeader(RESULT_CODE, '0');

        expect(resultCode).toStrictEqual(ResultCode.REPEAT.toString());
    });

    it('buffered pages', async () => {
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                isBuffered: true,
            },
        } as IInput);

        let result = await node.processAction(dto);
        const resultCode = result.getHeader(RESULT_CODE, '0');
        expect(resultCode).toStrictEqual(ResultCode.DO_NOT_CONTINUE.toString());

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
        const resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }, { id: 4 }],
            updated: [{ id: 2 }],
            deleted: [3],
        });
    });

    it('buffered pages out of order', async () => {
        const dto = new ProcessDto<IInput>();
        dto.setNewJsonData({
            items: [{ id: 1 }, { id: 2 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                isBuffered: true,
                isLast: true,
                totalCount: 4,
            },
        } as IInput);

        let result = await node.processAction(dto);
        const resultCode = result.getHeader(RESULT_CODE, '0');
        expect(resultCode).toStrictEqual(ResultCode.DO_NOT_CONTINUE.toString());

        // Second (last) page
        dto.setNewJsonData({
            items: [{ id: 3 }, { id: 4 }],
            configuration: {
                idField: 'id',
                masterKey: 'masterKey',
                isBuffered: true,
            },
        } as IInput);

        result = await node.processAction(dto);
        const resultData = result.getJsonData();

        expect(resultData).toStrictEqual({
            created: [{ id: 1 }, { id: 4 }],
            updated: [{ id: 2 }],
            deleted: [3],
        });
    });
});
