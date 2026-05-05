import FlexiBeeApplication from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';

export const NAME = 'flexibee-upload-attachment';

/**
 * Uploads a PDF attachment to a FlexiBee invoice (faktura-vydana).
 *
 * API: PUT /priloha/faktura-vydana-{flexiInvoiceId}/faktura.pdf
 * Content-Type: application/pdf
 * Body: raw PDF bytes
 *
 * Input:  { pdfBase64, flexiInvoiceCode, documentNumber, invoiceId, tagId, existingTags }
 * Output: { invoiceId, tagId, existingTags }
 */
export default class FlexiBeeUploadAttachmentConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;
        checkParams(data, ['pdfBase64', 'flexiInvoiceCode']);

        const pdfBase64 = data.pdfBase64 as string;
        const flexiInvoiceCode = data.flexiInvoiceCode as string;
        const documentNumber = data.documentNumber as string | undefined;

        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const application = this.getApplication<FlexiBeeApplication>();

        const filename = `inv-${documentNumber ?? flexiInvoiceCode}.pdf`;
        const url = application.getUrl(
            applicationInstall,
            `faktura-vydana/${flexiInvoiceCode}/prilohy/new/${filename}`,
        );

        // Build request manually — need binary body and application/pdf content type
        const pdfBuffer = Buffer.from(pdfBase64, 'base64');

        // Get a base request to inherit auth headers, then override content-type
        const baseRequest = await application.getRequestDto(
            dto,
            applicationInstall,
            HttpMethods.PUT,
            url,
        );
        const headers = baseRequest.getHeaders();
        headers['Content-Type'] = 'application/pdf';
        headers.Accept = 'application/json';

        const request = new RequestDto(
            url,
            HttpMethods.PUT,
            dto,
            pdfBuffer,
            headers,
        );

        await this.getSender().send(request, {
            success: [200, 201],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        // Pass through data for the tag connector
        dto.setJsonData({
            invoiceId: data.invoiceId,
            tagId: data.tagId,
            existingTags: data.existingTags,
        });

        return dto;
    }

}
