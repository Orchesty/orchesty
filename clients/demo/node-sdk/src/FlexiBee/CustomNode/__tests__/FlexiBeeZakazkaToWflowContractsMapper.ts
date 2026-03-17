import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as FLEXI_BEE_ZAKAZKA_TO_WFLOW_CONTRACTS_MAPPER } from '../FlexiBeeZakazkaToWflowContractsMapper';

let tester: NodeTester;

describe('Tests for FlexiBeeZakazkaToWflowContractsMapper', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(FLEXI_BEE_ZAKAZKA_TO_WFLOW_CONTRACTS_MAPPER);
    });
});
