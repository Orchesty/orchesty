import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as FLEXI_BEE_FIND_FIRMA_KOD_CONNECTOR } from '../FlexiBeeFindFirmaKodConnector';

let tester: NodeTester;

describe('Tests for FlexiBeeFindFirmaKodConnector', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testConnector(FLEXI_BEE_FIND_FIRMA_KOD_CONNECTOR);
    });
});
