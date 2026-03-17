import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as FLEXI_BEE_PREDPIS_ZAUCTOVANI_TO_WFLOW_ACCOUNTING_RULES_MAPPER } from '../FlexiBeePredpisZauctovaniToWflowAccountingRulesMapper';

let tester: NodeTester;

describe('Tests for FlexiBeePredpisZauctovaniToWflowAccountingRulesMapper', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(FLEXI_BEE_PREDPIS_ZAUCTOVANI_TO_WFLOW_ACCOUNTING_RULES_MAPPER);
    });
});
