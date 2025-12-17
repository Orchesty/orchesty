import { NAME as BEECEPTOR_APP_NAME } from '@orchesty/connector-beeceptor/dist/BeeceptorApplication';
import { FLEXI_BEE_APPLICATION, FLEXIBEE_URL } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { NAME as WFLOW_APP_NAME, ORGANIZATION, ORGANIZATION_FORM, WebhookType } from '@orchesty/connector-wflow/dist/WflowApplication';
import CoreFormsEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import {
    ApplicationInstall,
    IApplicationSettings,
} from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Webhook from '@orchesty/nodejs-sdk/dist/lib/Application/Database/Webhook';
import { ACCESS_TOKEN } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Provider/OAuth2/OAuth2Provider';
import { PASSWORD, TOKEN, USER } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import { CLIENT_ID, CLIENT_SECRET } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/OAuth2/IOAuth2Application';
import { getEnv, orchestyOptions } from '@orchesty/nodejs-sdk/dist/lib/Config/Config';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import { mockOnce } from '@orchesty/nodejs-sdk/dist/test/MockServer';
import { devIp } from '../.jest/testEnvs';
import { Topology } from '../src/Wflow/Enum/Topology';
import { FLEXI_BEE_FORM } from '../src/Wflow/WflowApplication';

export const DEFAULT_USER = 'TestUser';
export const DEFAULT_PASSWORD = 'Password';
export const DEFAULT_ACCESS_TOKEN = 'test-access-token';
export const DEFAULT_CLIENT_ID = 'test-client-id';
export const DEFAULT_CLIENT_SECRET = 'test-client-secret';

const MYSQL_APP_NAME = 'mysql';
const URL_KEY = 'url';
const HOST = 'host';
const PORT = 'port';
const DATABASE = 'database';

export function applicationInstall(
    name: string,
    user: string,
    settings: IApplicationSettings,
    nonEncryptedSettings: IApplicationSettings = {},
): ApplicationInstall {
    const app = new ApplicationInstall()
        .setEnabled(true)
        .setName(name)
        .setUser(user)
        .setSettings(settings)
        .setNonEncryptedSettings(nonEncryptedSettings);

    for (let i = 0; i < 2; i++) {
        mockOnce([
            {
                request: {
                    method: HttpMethods.GET,
                    url: new RegExp(
                        `${orchestyOptions.workerApi}/document/ApplicationInstall.*${name}.*`,
                    ),
                },
                response: {
                    code: 200,
                    body: [{ ...app.toArray(), settings }],
                },
            },
        ]);
    }

    return app;
}

export function beeceptorAppInstall(): ApplicationInstall {
    return applicationInstall(BEECEPTOR_APP_NAME, DEFAULT_USER, {
        [CoreFormsEnum.AUTHORIZATION_FORM]: {
            [URL_KEY]: 'https://test.free.beeceptor.com',
        },
    });
}

export function wflowAppInstall(): ApplicationInstall {
    return applicationInstall(WFLOW_APP_NAME, DEFAULT_USER, {
        [CoreFormsEnum.AUTHORIZATION_FORM]: {
            [CLIENT_ID]: DEFAULT_CLIENT_ID,
            [CLIENT_SECRET]: DEFAULT_CLIENT_SECRET,
            [TOKEN]: {
                [ACCESS_TOKEN]: DEFAULT_ACCESS_TOKEN,
            },
        },
        [ORGANIZATION_FORM]: {
            [ORGANIZATION]: 'test-organization',
        },
        [FLEXI_BEE_FORM]: {
            // eslint-disable-next-line @typescript-eslint/naming-convention
            '53b4b60a-30dc-4ac4-92cc-f7ceaf7b250a': 'demo',
        },
    });
}

export function flexiBeeAppInstall(): ApplicationInstall {
    return applicationInstall(FLEXI_BEE_APPLICATION, DEFAULT_USER, {
        [CoreFormsEnum.AUTHORIZATION_FORM]: {
            [FLEXIBEE_URL]: 'https://demo.flexibee.eu/c/demo',
            [USER]: DEFAULT_USER,
            [PASSWORD]: DEFAULT_PASSWORD,
            auth: 'http',
        },
    });
}

export function mySqlAppInstall(): ApplicationInstall {
    return applicationInstall(MYSQL_APP_NAME, DEFAULT_USER, {
        [CoreFormsEnum.AUTHORIZATION_FORM]: {
            [HOST]: getEnv('MYSQL_HOST', devIp),
            [PORT]: getEnv('MYSQL_PORT', '3306'),
            [USER]: getEnv('MYSQL_USER', 'root'),
            [PASSWORD]: getEnv('MYSQL_ROOT_PASSWORD', 'password'),
            [DATABASE]: getEnv('MYSQL_DATABASE', 'orchesty'),
        },
    });
}

export function mockSubscribeWflowWebhook(): void {
    mockOnce([
        {
            request: {
                method: HttpMethods.GET,
                url: new RegExp(
                    `${orchestyOptions.workerApi}/document/Webhook.*`,
                ),
            },
            response: {
                code: 200,
                body: [],
            },
        },
    ]);

    mockOnce([
        {
            request: {
                method: HttpMethods.GET,
                url: new RegExp(
                    `${orchestyOptions.workerApi}/document/Webhook.*`,
                ),
            },
            response: {
                code: 200,
                body: [
                    new Webhook()
                        .setWebhookId('252aea1a-056e-4ae3-87d1-11bde1345c19')
                        .setApplication(WFLOW_APP_NAME)
                        .setName(WebhookType.DOCUMENT_READY_TO_EXPORT)
                        .setNode('document-ready-to-extract')
                        .setTopology(Topology.WFLOW_TO_FLEXIBEE_FAKTURA_PRIJATA)
                        .setUnsubscribeFailed(false)
                        .setUser(DEFAULT_USER)
                        .setToken('token')
                        .toArray(),
                ],
            },
        },
    ]);
}

export function mockUnsubscribeWflowWebhook(): void {
    mockOnce([
        {
            request: {
                method: HttpMethods.GET,
                url: new RegExp(
                    `${orchestyOptions.workerApi}/document/Webhook.*`,
                ),
            },
            response: {
                code: 200,
                body: [
                    new Webhook()
                        .setWebhookId('252aea1a-056e-4ae3-87d1-11bde1345c19')
                        .setApplication(WFLOW_APP_NAME)
                        .setName(WebhookType.DOCUMENT_READY_TO_EXPORT)
                        .setNode('document-ready-to-extract')
                        .setTopology(Topology.WFLOW_TO_FLEXIBEE_FAKTURA_PRIJATA)
                        .setUnsubscribeFailed(false)
                        .setUser(DEFAULT_USER)
                        .setToken('token')
                        .toArray(),
                ],
            },
        },
    ]);

    mockOnce([
        {
            request: {
                method: HttpMethods.GET,
                url: new RegExp(
                    `${orchestyOptions.workerApi}/document/Webhook.*`,
                ),
            },
            response: {
                code: 200,
                body: [],
            },
        },
    ]);
}
