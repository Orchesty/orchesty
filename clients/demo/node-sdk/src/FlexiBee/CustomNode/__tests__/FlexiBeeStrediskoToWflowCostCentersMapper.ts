import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as FLEXI_BEE_STREDISKO_TO_WFLOW_COST_CENTERS_MAPPER } from '../FlexiBeeStrediskoToWflowCostCentersMapper';

let tester: NodeTester;

describe('Tests for FlexiBeeStrediskoToWflowCostCentersMapper', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(FLEXI_BEE_STREDISKO_TO_WFLOW_COST_CENTERS_MAPPER);
    });
});
