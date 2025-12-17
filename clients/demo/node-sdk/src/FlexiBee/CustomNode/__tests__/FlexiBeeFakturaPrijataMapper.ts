import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as FLEXI_BEE_FAKTURA_PRIJATA_MAPPER } from '../FlexiBeeFakturaPrijataMapper';

let tester: NodeTester;

describe('Tests for FlexiBeeFakturaPrijataMapper', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(FLEXI_BEE_FAKTURA_PRIJATA_MAPPER);
    });
});
