import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { BASE_URL } from '../IDokladClientCredentialsApplication';

export const NAME = 'i-doklad-create-issued-invoice';

export default class IDokladCreateIssuedInvoiceConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;
        checkParams(data, [
            'PartnerId',
            'Description',
            'DateOfIssue',
            'DateOfMaturity',
            'DateOfTaxing',
            'PaymentOptionId',
            'Items',
        ]);

        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const request = await this.getApplication().getRequestDto(
            dto,
            applicationInstall,
            HttpMethods.POST,
            `${BASE_URL}/IssuedInvoices`,
            dto.getData(),
        );

        const response = await this.getSender().send(request, {
            success: [200, 201],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);
        dto.setData(response.getBody());

        return dto;
    }

}
