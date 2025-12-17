import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as JSON_PLACEHOLDER_GET_POST_USER_CONNECTOR } from '../JsonPlaceholderGetPostUserConnector';

let tester: NodeTester;

describe('Tests for JsonPlaceholderGetPostUserConnector', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testConnector(JSON_PLACEHOLDER_GET_POST_USER_CONNECTOR);
    });
});
