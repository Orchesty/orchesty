import CoreFormsEnum, { getFormName } from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import { ABasicApplication, PASSWORD, USER } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import { CommonHeaders, JSON_TYPE } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';

// ── iDoklad API v3 base URL ──
export const BASE_URL = 'https://api.idoklad.cz/v3';

// ── iDoklad Identity Server ──
const TOKEN_URL = 'https://identity.idoklad.cz/server/connect/token';

// ── In-memory token cache ──
interface CachedToken {
    accessToken: string;
    expiresAt: number; // Unix timestamp in ms
}

const tokenCache = new Map<string, CachedToken>();

/**
 * iDoklad application using the **Client Credentials** OAuth2 flow.
 *
 * This is designed for internal / server-to-server usage where no
 * interactive browser login is required.  The user provides their
 * `client_id` and `client_secret` (from iDoklad user settings → API),
 * and the application exchanges them for an access token directly.
 */
export default class IDokladClientCredentialsApplication extends ABasicApplication {

    public getName(): string {
        return 'i-doklad';
    }

    public getPublicName(): string {
        return 'iDoklad';
    }

    public getDescription(): string {
        return 'iDoklad accounting service (Client Credentials)';
    }

    public getLogo(): string {
        return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZmlsbD0iI0ZGNkIzNSIgZD0iTTE0IDJINmMtMS4xIDAtMS45OS45LTEuOTkgMkw0IDIwYzAgMS4xLjg5IDIgMS45OSAySDE4YzEuMSAwIDItLjkgMi0yVjhsLTYtNnptMiAxNkg4di0yaDh2MnptMC00SDh2LTJoOHYyem0tMy01VjMuNUwxOC41IDlIMTN6Ii8+PC9zdmc+';
    }

    // ── Form ──

    public getFormStack(): FormStack {
        const form = new Form(
            CoreFormsEnum.AUTHORIZATION_FORM,
            getFormName(CoreFormsEnum.AUTHORIZATION_FORM),
        )
            .addField(new Field(FieldType.TEXT, USER, 'Client Id', null, true))
            .addField(new Field(FieldType.TEXT, PASSWORD, 'Client Secret', null, true));

        return new FormStack().addForm(form);
    }

    public isAuthorized(applicationInstall: ApplicationInstall): boolean {
        const form = applicationInstall.getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM];
        return !!form?.[USER] && !!form?.[PASSWORD];
    }

    // ── Request building ──

    public async getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: string,
        url?: string,
        data?: unknown,
    ): Promise<RequestDto> {
        const accessToken = await this.getAccessToken(applicationInstall);

        return new RequestDto(
            url ?? BASE_URL,
            method as HttpMethods,
            dto,
            data,
            {
                [CommonHeaders.CONTENT_TYPE]: JSON_TYPE,
                [CommonHeaders.ACCEPT]: JSON_TYPE,
                [CommonHeaders.AUTHORIZATION]: `Bearer ${accessToken}`,
            },
        );
    }

    // ── Token management ──

    private async getAccessToken(applicationInstall: ApplicationInstall): Promise<string> {
        const settings = applicationInstall.getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM];
        const clientId = settings?.[USER] as string;
        const clientSecret = settings?.[PASSWORD] as string;

        if (!clientId || !clientSecret) {
            throw new Error('iDoklad Client Id or Client Secret is missing.');
        }

        // Return cached token if still valid (with 60 s safety buffer)
        const cached = tokenCache.get(clientId);
        if (cached && cached.expiresAt > Date.now() + 60_000) {
            return cached.accessToken;
        }

        // Exchange credentials for a new token
        /* eslint-disable @typescript-eslint/naming-convention */
        const body = new URLSearchParams({
            grant_type: 'client_credentials',
            client_id: clientId,
            client_secret: clientSecret,
            scope: 'idoklad_api',
        });
        /* eslint-enable @typescript-eslint/naming-convention */

        const response = await fetch(TOKEN_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        });

        if (!response.ok) {
            const errorBody = await response.text();
            throw new Error(
                `iDoklad token request failed [${response.status}]: ${errorBody}`,
            );
        }

        const tokenData = (await response.json()) as {
            access_token: string;
            expires_in: number;
            token_type: string;
        };

        // Cache the token
        tokenCache.set(clientId, {
            accessToken: tokenData.access_token,
            expiresAt: Date.now() + tokenData.expires_in * 1_000,
        });

        return tokenData.access_token;
    }

}
