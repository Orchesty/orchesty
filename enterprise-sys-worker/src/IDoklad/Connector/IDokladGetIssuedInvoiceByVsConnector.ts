import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { BASE_URL } from '../IDokladClientCredentialsApplication';

export const NAME = 'i-doklad-get-issued-invoice-by-vs';

/**
 * Fetches a single issued invoice from iDoklad by variable symbol (VS).
 *
 * Input:  { vs: "202608" }
 * Output: flat invoice object (extracted from Data.Items[0])
 */
export default class IDokladGetIssuedInvoiceByVsConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;
        checkParams(data, ['vs']);

        const vs = data.vs as string;
        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const request = await this.getApplication().getRequestDto(
            dto,
            applicationInstall,
            HttpMethods.GET,
            `${BASE_URL}/IssuedInvoices?filter=DocumentNumber~eq~${encodeURIComponent(vs)}&pageSize=1`,
        );

        const response = await this.getSender().send(request, {
            success: [200],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        /* eslint-disable @typescript-eslint/naming-convention */
        const body = JSON.parse(response.getBody()) as {
            Data?: { Items?: Record<string, unknown>[] };
        };
        /* eslint-enable @typescript-eslint/naming-convention */
        const invoice = body.Data?.Items?.[0];

        if (!invoice) {
            throw new Error(`Invoice with DocumentNumber [${vs}] not found in iDoklad`);
        }

        dto.setJsonData(invoice);

        return dto;
    }

}
