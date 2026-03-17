import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as FLEXI_BEE_CLENENI_DPH_TO_WFLOW_VAT_RETURN_LINES_MAPPER } from '../FlexiBeeCleneniDphToWflowVatReturnLinesMapper';

let tester: NodeTester;

describe('Tests for FlexiBeeCleneniDphToWflowVatReturnLinesMapper', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(FLEXI_BEE_CLENENI_DPH_TO_WFLOW_VAT_RETURN_LINES_MAPPER);
    });

    it('process - non-cz', async () => {
        await tester.testCustomNode(FLEXI_BEE_CLENENI_DPH_TO_WFLOW_VAT_RETURN_LINES_MAPPER, 'non-cz');
    });
});
