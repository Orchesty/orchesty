import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as FLEXI_BEE_CLENENI_KONTROLNI_HLASENI_TO_WFLOW_VAT_CONTROL_STATEMENT_LINES_MAPPER } from '../FlexiBeeCleneniKontrolniHlaseniToWflowVatControlStatementLinesMapper';

let tester: NodeTester;

describe('Tests for FlexiBeeCleneniKontrolniHlaseniToWflowVatControlStatementLinesMapper', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(FLEXI_BEE_CLENENI_KONTROLNI_HLASENI_TO_WFLOW_VAT_CONTROL_STATEMENT_LINES_MAPPER);
    });

    it('process - non-cz', async () => {
        await tester.testCustomNode(FLEXI_BEE_CLENENI_KONTROLNI_HLASENI_TO_WFLOW_VAT_CONTROL_STATEMENT_LINES_MAPPER, 'non-cz');
    });
});
