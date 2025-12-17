import { container } from '@orchesty/nodejs-sdk';
import { orchestyOptions } from '@orchesty/nodejs-sdk/dist/lib/Config/Config';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import { mockOnce } from '@orchesty/nodejs-sdk/dist/test/MockServer';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as BEECEPTOR_CREATE_WEBHOOKS_BATCH } from '../BeeceptorCreateWebhooks';
import webhooks from './Data/webhook.json';

let tester: NodeTester;

describe('Tests for BeeceptorCreateWebhooks', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();

        webhooks.reverse();
        for (let i = webhooks.length; i >= 0; i--) {
            mockOnce([{
                request: {
                    method: HttpMethods.GET,
                    url: new RegExp(
                        `${orchestyOptions.workerApi}/document/Webhook.*`,
                    ),
                },
                response: {
                    code: 200,
                    body: webhooks.slice(i, webhooks.length),
                },
            }]);
        }
    });

    it('process - ok', async () => {
        await tester.testBatch(BEECEPTOR_CREATE_WEBHOOKS_BATCH);
    });
});
