import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as WFLOW_WEBHOOK_PAYLOAD_MAPPER } from '../WflowWebhookPayloadMapper';

let tester: NodeTester;

describe('Tests for WflowWebhookPayloadMapper', () => {
    beforeEach(() => {
        tester = new NodeTester(container, __filename);
        prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(WFLOW_WEBHOOK_PAYLOAD_MAPPER);
    });
});
