import { WebhookType } from '@orchesty/connector-wflow/dist/WflowApplication';
import { container } from '@orchesty/nodejs-sdk';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import TopologyTester from '@orchesty/nodejs-sdk/dist/test/Testers/TopologyTester';
import path from 'path';
import { DEFAULT_USER } from '../../../test/DataProvider';
import { prepare } from '../../../test/TestAbstract';

let tester: TopologyTester;
const TOPOLOGY_PATH = path.resolve(
    process.cwd(),
    'src',
    'topology',
    'wflow-flexi-bee',
    'wflow-to-flexi-bee-faktura-prijata.tplg',
);

describe('Tests for WflowToFlexiBeeFakturaPrijata topology', () => {
    beforeAll(() => {
        tester = new TopologyTester(container, __filename, true, ['Activity_032ocy1', 'Activity_1792a8q', 'Activity_1vja3mz']);
        prepare();
    });

    it('run WflowToFlexiBeeFakturaPrijata manually', async () => {
        const dto = new ProcessDto();
        dto.setUser(DEFAULT_USER);
        dto.setJsonData({
            notification: {
                organization: 'test-organization',
                documentId: 'test-document',
                action: WebhookType.DOCUMENT_READY_TO_EXPORT,
            },
            registrationId: 'test-webhook-id',
            id: 'test-trace-id',
        });

        await tester.runTopology(TOPOLOGY_PATH, dto, undefined, 'document-ready-to-export');
    });
});
