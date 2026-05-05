import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { BASE_URL } from '../IDokladClientCredentialsApplication';

export const NAME = 'i-doklad-tag-issued-invoice';

export default class IDokladTagIssuedInvoiceConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;
        checkParams(data, ['invoiceId', 'tagId']);

        const invoiceId = data.invoiceId as number;
        const tagId = data.tagId as number;
        const existingTags = (data.existingTags ?? []) as number[];

        const tags = [...new Set([...existingTags, tagId])];

        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const request = await this.getApplication().getRequestDto(
            dto,
            applicationInstall,
            HttpMethods.PATCH,
            `${BASE_URL}/IssuedInvoices`,
            JSON.stringify({ Id: invoiceId, Tags: tags }),
        );

        const response = await this.getSender().send(request, {
            success: [200],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);
        dto.setData(response.getBody());

        return dto;
    }

}
