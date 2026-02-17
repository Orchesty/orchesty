import {
    GROUP_TIME,
    GROUP_VALUE,
    TIME,
    USE_LIMIT,
    VALUE,
} from '@orchesty/nodejs-sdk/dist/lib/Application/Base/AApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import { ABasicApplication } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Type/Basic/ABasicApplication';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';

export const NAME = 'http-status-node-js';

export default class HttpStatusApplication extends ABasicApplication {

    public getName(): string {
        return NAME;
    }

    public getDescription(): string {
        return 'Easily check status codes, response headers, and redirect chains.';
    }

    public getPublicName(): string {
        return 'HTTP Status (Node.js)';
    }

    public getLogo(): string | null {
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODQ2IiBoZWlnaHQ9Ijg0NiIgdmlld0JveD0iMCAwIDg0NiA4NDYiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxjaXJjbGUgY3g9IjQyMyIgY3k9IjQyMyIgcj0iNDIzIiBmaWxsPSIjMDA5OUZGIi8+CjxwYXRoIGZpbGwtcnVsZT0iZXZlbm9kZCIgY2xpcC1ydWxlPSJldmVub2RkIiBkPSJNNDQ3LjY4NCAxNzAuOTU4QzQ0MS41MTkgMTY4LjUyMyA0MzIuNTU2IDE3MC45NDQgNDE0LjYyOCAxNzUuNzg1TDIzOC43MTMgMjIzLjI5MkMyMjAuODkxIDIyOC4xMDUgMjExLjk3OSAyMzAuNTExIDIwNy44ODIgMjM1LjY5NUMyMDQuNDYxIDI0MC4wMjQgMjAyLjk4IDI0NS41NzMgMjAzLjc4OSAyNTEuMDNDMjA0Ljc1OCAyNTcuNTY3IDIxMS4yODUgMjY0LjA5MyAyMjQuMzM5IDI3Ny4xNDdMMjUzLjIyNCAzMDYuMDMyTDE0Ny4xMDEgNDEyLjE1NUMxNDEuNjMzIDQxNy42MjIgMTQxLjYzMyA0MjYuNDg3IDE0Ny4xMDEgNDMxLjk1NEwxOTcuOTU3IDQ4Mi44MTFDMjAzLjQyNSA0ODguMjc4IDIxMi4yODkgNDg4LjI3OCAyMTcuNzU2IDQ4Mi44MTFMMzIzLjg4IDM3Ni42ODhMMzUyLjc0OCA0MDUuNTU2QzM2NS44MDEgNDE4LjYxIDM3Mi4zMjggNDI1LjEzNiAzNzguODY1IDQyNi4xMDZDMzg0LjMyMiA0MjYuOTE1IDM4OS44NzEgNDI1LjQzNCAzOTQuMiA0MjIuMDEzQzM5OS4zODQgNDE3LjkxNSA0MDEuNzkgNDA5LjAwNCA0MDYuNjAzIDM5MS4xODJMNDU0LjEwOSAyMTUuMjY3TDQ1NC4xMSAyMTUuMjY2QzQ1OC45NTEgMTk3LjMzOSA0NjEuMzcyIDE4OC4zNzUgNDU4LjkzNiAxODIuMjExQzQ1Ni45MDQgMTc3LjA2NSA0NTIuODMgMTcyLjk5MSA0NDcuNjg0IDE3MC45NThaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTM4Ny4wNDggNjYzLjc4OUMzODQuNjEzIDY1Ny42MjUgMzg3LjAzNCA2NDguNjYxIDM5MS44NzUgNjMwLjczNEw0MzkuMzgyIDQ1NC44MThDNDQ0LjE5NSA0MzYuOTk2IDQ0Ni42MDEgNDI4LjA4NSA0NTEuNzg1IDQyMy45ODhDNDU2LjExMyA0MjAuNTY3IDQ2MS42NjMgNDE5LjA4NSA0NjcuMTIgNDE5Ljg5NUM0NzMuNjU2IDQyMC44NjQgNDgwLjE4MyA0MjcuMzkxIDQ5My4yMzcgNDQwLjQ0NEw1MjIuMTIyIDQ2OS4zMjlMNjI4LjI0MSAzNjMuMjFDNjMzLjcwOCAzNTcuNzQzIDY0Mi41NzMgMzU3Ljc0MyA2NDguMDQgMzYzLjIxTDY5OC44OTcgNDE0LjA2N0M3MDQuMzY0IDQxOS41MzQgNzA0LjM2NCA0MjguMzk5IDY5OC44OTcgNDMzLjg2Nkw1OTIuNzc4IDUzOS45ODVMNjIxLjY0NiA1NjguODUzQzYzNC43IDU4MS45MDcgNjQxLjIyNiA1ODguNDM0IDY0Mi4xOTYgNTk0Ljk3QzY0My4wMDUgNjAwLjQyNyA2NDEuNTI0IDYwNS45NzcgNjM4LjEwMyA2MTAuMzA1QzYzNC4wMDUgNjE1LjQ4OSA2MjUuMDk0IDYxNy44OTYgNjA3LjI3MiA2MjIuNzA5TDQzMS4zNTcgNjcwLjIxNUw0MzEuMzU2IDY3MC4yMTVDNDEzLjQyOSA2NzUuMDU2IDQwNC40NjUgNjc3LjQ3NyAzOTguMzAxIDY3NS4wNDJDMzkzLjE1NSA2NzMuMDA5IDM4OS4wODEgNjY4LjkzNSAzODcuMDQ4IDY2My43ODlaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K';
    }

    public getFormStack(): FormStack {
        return new FormStack();
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public isAuthorized(applicationInstall: ApplicationInstall): boolean {
        return true;
    }

    public getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: string,
        url?: string,
        data?: unknown, // eslint-disable-line @typescript-eslint/no-unused-vars
    ): Promise<RequestDto> | RequestDto {
        return new RequestDto(`https://mock.httpstatus.io/${url}`, HttpMethods.GET, dto);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public getGlobalLimits(applicationInstall: ApplicationInstall): Limits {
        return {
            [USE_LIMIT]: false,
            [TIME]: 60,
            [VALUE]: 60,
        };
    }

}

interface Limits {
    [USE_LIMIT]?: boolean;
    [TIME]?: number;
    [VALUE]?: number;
    [GROUP_TIME]?: number;
    [GROUP_VALUE]?: number;
}
