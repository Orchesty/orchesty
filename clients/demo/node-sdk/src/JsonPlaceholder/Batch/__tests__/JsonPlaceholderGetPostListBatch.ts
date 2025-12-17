import { NAME as JSON_PLACEHOLDER_GET_POST_LIST_BATCH } from '@orchesty/connector-json-placeholder/dist/Batch/JsonPlaceholderGetPostListBatch';
import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';

let tester: NodeTester;

describe('Tests for JsonPlaceholderGetPostCommentListBatch', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testBatch(JSON_PLACEHOLDER_GET_POST_LIST_BATCH);
    });
});
