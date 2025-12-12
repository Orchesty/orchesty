import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as JSON_PLACEHOLDER_TO_BEECEPTOR_SYNC_POST_MAPPER } from '../JsonPlaceholderToBeeceptorSyncPostMapper';

let tester: NodeTester;

describe('Tests for JsonPlaceholderToBeeceptorSyncPostMapper', () => {
    beforeEach(() => {
        tester = new NodeTester(container, __filename);
        prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(
            JSON_PLACEHOLDER_TO_BEECEPTOR_SYNC_POST_MAPPER,
        );
    });
});
