import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as BEECEPTOR_PUT_PRODUCT_CATEGORIES_CONNECTOR } from '../BeeceptorPutProductCategoriesConnector';

let tester: NodeTester;

describe('Tests for BeeceptorPutProductCategoriesConnector', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testConnector(BEECEPTOR_PUT_PRODUCT_CATEGORIES_CONNECTOR);
    });
});
