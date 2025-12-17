import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as BEECEPTOR_PUT_PRODUCT_CONNECTOR } from '../BeeceptorPutProductConnector';

let tester: NodeTester;

describe('Tests for BeeceptorPutProductConnector', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testConnector(BEECEPTOR_PUT_PRODUCT_CONNECTOR);
    });
});
