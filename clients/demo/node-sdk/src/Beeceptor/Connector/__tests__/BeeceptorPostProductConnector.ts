import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as BEECEPTOR_POST_PRODUCT_CONNECTOR } from '../BeeceptorPostProductConnector';

let tester: NodeTester;

describe('Tests for BeeceptorPostProductConnector', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testConnector(BEECEPTOR_POST_PRODUCT_CONNECTOR);
    });
});
