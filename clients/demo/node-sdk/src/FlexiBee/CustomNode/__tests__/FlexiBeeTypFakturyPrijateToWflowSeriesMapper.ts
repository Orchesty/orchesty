import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as FLEXI_BEE_TYP_FAKTURY_PRIJATE_TO_WFLOW_SERIES_MAPPER } from '../FlexiBeeTypFakturyPrijateToWflowSeriesMapper';

let tester: NodeTester;

describe('Tests for FlexiBeeTypFakturyPrijateToWflowSeriesMapper', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(FLEXI_BEE_TYP_FAKTURY_PRIJATE_TO_WFLOW_SERIES_MAPPER);
    });
});
