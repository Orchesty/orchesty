import { container } from '@orchesty/nodejs-sdk';
import { orchestyOptions } from '@orchesty/nodejs-sdk/dist/lib/Config/Config';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { mockOnce } from '@orchesty/nodejs-sdk/dist/test/MockServer';
import TopologyTester from '@orchesty/nodejs-sdk/dist/test/Testers/TopologyTester';
import path from 'path';
import { DEFAULT_USER } from '../../../test/DataProvider';
import { prepare } from '../../../test/TestAbstract';
import webhooks from '../../Beeceptor/Batch/__tests__/Data/webhook.json';

let tester: TopologyTester;
const TOPOLOGY_PATH = path.resolve(
    process.cwd(),
    'src',
    'topology',
    'mysql-beeceptor',
    'mysql-to-beeceptor-webhooks.tplg',
);

describe('Tests for MySqlToBeeceptorWebhooks topology', () => {
    beforeAll(async () => {
        tester = new TopologyTester(container, __filename, true, []);
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

        webhooks.reverse();
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

    it('run MySqlToBeeceptorWebhooks subscribe', async () => {
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
