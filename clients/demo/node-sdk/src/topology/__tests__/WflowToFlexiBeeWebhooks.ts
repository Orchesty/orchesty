import { container } from '@orchesty/nodejs-sdk';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import TopologyTester from '@orchesty/nodejs-sdk/dist/test/Testers/TopologyTester';
import path from 'path';
import { DEFAULT_USER, mockSubscribeWflowWebhook, mockUnsubscribeWflowWebhook } from '../../../test/DataProvider';
import { prepare } from '../../../test/TestAbstract';

let tester: TopologyTester;
const TOPOLOGY_PATH = path.resolve(
    process.cwd(),
    'src',
    'topology',
    'wflow-flexi-bee',
    'wflow-to-flexi-bee-webhooks.tplg',
);

describe('Tests for WflowToFlexiBeeWebhooks topology', () => {
    beforeAll(async () => {
        tester = new TopologyTester(container, __filename, true);
        await prepare();
        mockSubscribeWflowWebhook();
        mockUnsubscribeWflowWebhook();
    });

    it('run WflowToFlexiBeeWebhooks manually', async () => {
        await tester.runTopology(
            TOPOLOGY_PATH,
            new ProcessDto()
                .setUser(DEFAULT_USER)
                .setJsonData({}),
            undefined,
            'subscribe',
        );

        await tester.runTopology(
            TOPOLOGY_PATH,
            new ProcessDto()
                .setUser(DEFAULT_USER)
                .setJsonData({}),
            undefined,
            'unsubscribe',
        );
    });
});
