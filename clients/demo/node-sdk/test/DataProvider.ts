import { NAME as BEECEPTOR_APP_NAME } from '@orchesty/nodejs-connectors/dist/lib/Beeceptor/BeeceptorApplication';
import CoreFormsEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import {
    ApplicationInstall,
    IApplicationSettings,
} from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import { orchestyOptions } from '@orchesty/nodejs-sdk/dist/lib/Config/Config';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import { mockOnce } from '@orchesty/nodejs-sdk/dist/test/MockServer';

export const DEFAULT_USER = 'TestUser';

const URL_KEY = 'url';

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

    mockOnce([
        {
            request: {
                method: HttpMethods.GET,
                url: new RegExp(
                    `${orchestyOptions.workerApi}/document/ApplicationInstall.*`,
                ),
            },
            response: {
                code: 200,
                body: [{ ...app.toArray(), settings }],
            },
        },
    ]);

    return app;
}

export function beeceptorAppInstall(): ApplicationInstall {
    return applicationInstall(BEECEPTOR_APP_NAME, DEFAULT_USER, {
        [CoreFormsEnum.AUTHORIZATION_FORM]: {
            [URL_KEY]: 'https://test.free.beeceptor.com',
        },
    });
}
