import { NAME as BATCH_NAME } from '@orchesty/connector-sql/dist/Common/ASqlBatchConnector';
import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';

const MYSQL_GET_PRODUCT_LIST_BATCH = `get-product-list-${BATCH_NAME}`;

let tester: NodeTester;

describe('Tests for MySqlGetProductListBatch', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testBatch(MYSQL_GET_PRODUCT_LIST_BATCH);
    });
});
