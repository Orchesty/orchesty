import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { NAME as COMPARATOR_INVALIDATE } from '../../src/custom_node/ComparatorInvalidate';
import { ComparatorHash } from '../../src/model';
import { ComparatorHashRepository } from '../../src/service/storage/repository';

let tester: NodeTester;

describe('Tests for ComparatorInvalidate', () => {
    let repository: ComparatorHashRepository;

    beforeAll(() => {
        tester = new NodeTester(container, __filename);
        repository = container.get(ComparatorHashRepository);
    });

    beforeEach(async () => {
        await repository.insert(new ComparatorHash('invalidateKey', 'hash', 'id'));
        await repository.insert(new ComparatorHash('invalidateKey', 'hash', 'id2'));
    });

    it('process - ok', async () => {
        await tester.testCustomNode(COMPARATOR_INVALIDATE);

        const dataSet = await repository.find({ masterKey: 'invalidateKey' });
        expect(dataSet).toHaveLength(0);
    });

    it('process - externalid', async () => {
        await tester.testCustomNode(COMPARATOR_INVALIDATE, 'externalid');

        const dataSet = await repository.find({ masterKey: 'invalidateKey' });
        expect(dataSet).toHaveLength(1);
    });
});
