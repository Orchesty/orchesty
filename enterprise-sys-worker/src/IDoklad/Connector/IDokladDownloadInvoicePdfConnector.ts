import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { BASE_URL } from '../IDokladClientCredentialsApplication';

export const NAME = 'i-doklad-download-invoice-pdf';

/**
 * Downloads the PDF report for an issued invoice from iDoklad.
 *
 * API: GET /Reports/IssuedInvoice/{id}/Pdf
 * Returns base64-encoded PDF in { Data: "base64...", IsSuccess: true }
 *
 * Input:  { invoiceId, flexiInvoiceCode, documentNumber, tagId, existingTags }
 * Output: { pdfBase64, flexiInvoiceCode, documentNumber, invoiceId, tagId, existingTags }
 */
export default class IDokladDownloadInvoicePdfConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;
        checkParams(data, ['invoiceId']);

        const invoiceId = data.invoiceId as number;
        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const request = await this.getApplication().getRequestDto(
            dto,
            applicationInstall,
            HttpMethods.GET,
            `${BASE_URL}/Reports/IssuedInvoice/${invoiceId}/Pdf`,
        );

        const response = await this.getSender().send(request, {
            success: [200],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        const body = JSON.parse(response.getBody()) as {
            Data?: string;
            IsSuccess?: boolean;
        };

        if (!body.Data) {
            throw new Error(`Failed to download PDF for invoice [${invoiceId}]`);
        }

        dto.setJsonData({
            pdfBase64: body.Data,
            flexiInvoiceCode: data.flexiInvoiceCode,
            documentNumber: data.documentNumber,
            invoiceId: data.invoiceId,
            tagId: data.tagId,
            existingTags: data.existingTags,
        });

        return dto;
    }

}
