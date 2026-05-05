import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { BASE_URL } from '../IDokladClientCredentialsApplication';

export const NAME = 'i-doklad-prepare-issued-invoice';

/**
 * Fetches the default issued-invoice template from iDoklad
 * (GET /IssuedInvoices/Default) and merges the incoming payload on top.
 *
 * Input:  partial invoice data (e.g. PartnerId, Description, Items, …)
 * Output: complete invoice data ready for POST /IssuedInvoices
 */
export default class IDokladPrepareIssuedInvoiceConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const customData = dto.getJsonData() as Record<string, unknown>;

        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const request = await this.getApplication().getRequestDto(
            dto,
            applicationInstall,
            HttpMethods.GET,
            `${BASE_URL}/IssuedInvoices/Default`,
        );

        const response = await this.getSender().send(request, {
            success: [200],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        // eslint-disable-next-line @typescript-eslint/naming-convention
        const body = JSON.parse(response.getBody()) as { Data?: Record<string, unknown> };
        const defaults = body.Data ?? {};

        // Custom data overrides defaults; Items are fully replaced (not merged)
        dto.setJsonData({ ...defaults, ...customData });

        return dto;
    }

}
