import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as WFLOW_DOCUMENT_MAIN_FILE_TO_FLEXIBEE_FAKTURA_PRIJATA_PRILOHA_MAPPER } from '../WflowDocumentMainFileToFlexiBeeFakturaPrijataPrilohaMapper';

let tester: NodeTester;

describe('Tests for WflowDocumentMainFileToFlexiBeeFakturaPrijataPrilohaMapper', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename, false, undefined, { useRawInputData: true });
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(WFLOW_DOCUMENT_MAIN_FILE_TO_FLEXIBEE_FAKTURA_PRIJATA_PRILOHA_MAPPER);
    });
});
