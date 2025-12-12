import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as JSON_PLACEHOLDER_GET_POST_COMMENT_LIST_BATCH } from '../JsonPlaceholderGetPostCommentListBatch';

let tester: NodeTester;

describe('Tests for JsonPlaceholderGetPostCommentListBatch', () => {
    beforeEach(() => {
        tester = new NodeTester(container, __filename);
        prepare();
    });

    it('process - ok', async () => {
        await tester.testBatch(JSON_PLACEHOLDER_GET_POST_COMMENT_LIST_BATCH);
    });
});
