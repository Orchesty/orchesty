import BaseFlexiBeeApplication from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import AProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import crypto from 'crypto';

export const CUSTOM_COMPANY_ID = 'customCompanyId';

export class FlexiBeeApplication extends BaseFlexiBeeApplication {

    public static createCode(value: string): string {
        return crypto
            .createHash('md5')
            .update(value.trim().toLowerCase())
            .digest('hex')
            .substring(0, 20)
            .toUpperCase();
    }

    public async getRequestDto(
        dto: AProcessDto,
        applicationInstall: ApplicationInstall,
        method: HttpMethods,
        url?: string,
        data?: unknown,
    ): Promise<RequestDto> {
        const requestDto = await super.getRequestDto(dto, applicationInstall, method, url, data);
        const customCompanyId = dto.getHeader(CUSTOM_COMPANY_ID);

        if (customCompanyId) {
            requestDto.setUrl(requestDto.getUrl().replace(/\/c\/[^/]+/, `/c/${customCompanyId}`));
        }

        return requestDto;
    }

}
