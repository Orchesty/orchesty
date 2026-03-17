import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as FLEXI_BEE_UCET_TO_WFLOW_CHART_OF_ACCOUNTS_MAPPER } from '../FlexiBeeUcetToWflowChartOfAccountsMapper';

let tester: NodeTester;

describe('Tests for FlexiBeeUcetToWflowChartOfAccountsMapper', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(FLEXI_BEE_UCET_TO_WFLOW_CHART_OF_ACCOUNTS_MAPPER);
    });
});
