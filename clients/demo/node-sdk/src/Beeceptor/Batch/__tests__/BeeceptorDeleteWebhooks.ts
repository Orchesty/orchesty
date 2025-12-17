import { container } from '@orchesty/nodejs-sdk';
import { orchestyOptions } from '@orchesty/nodejs-sdk/dist/lib/Config/Config';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import { mockOnce } from '@orchesty/nodejs-sdk/dist/test/MockServer';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as BEECEPTOR_DELETE_WEBHOOKS_BATCH } from '../BeeceptorDeleteWebhooks';
import webhooks from './Data/webhook.json';

let tester: NodeTester;

describe('Tests for BeeceptorDeleteWebhooks', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();

        [...webhooks, null].forEach((webhook) => {
            mockOnce([{
                request: {
                    method: HttpMethods.GET,
                    url: new RegExp(
                        `${orchestyOptions.workerApi}/document/Webhook.*`,
                    ),
                },
                response: {
                    code: 200,
                    body: webhook ? [webhook] : [],
                },
            }]);
        });
    });

    it('process - ok', async () => {
        await tester.testBatch(BEECEPTOR_DELETE_WEBHOOKS_BATCH);
    });
});
