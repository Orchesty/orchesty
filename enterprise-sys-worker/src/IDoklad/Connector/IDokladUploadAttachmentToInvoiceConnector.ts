import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { BASE_URL } from '../IDokladClientCredentialsApplication';

export const NAME = 'i-doklad-upload-attachment-to-invoice';

export interface IInput {
    invoiceId: string;
    iDokladInvoiceId: string;
    reportPdf?: {
        filename: string;
        contentBase64: string;
    };
}

export interface IOutput {
    invoiceId: string;
    iDokladInvoiceId: string;
}

const DOCUMENT_TYPE_ISSUED_INVOICE = 0;

/**
 * Uploads a PDF attachment to an issued invoice in iDoklad.
 *
 * API: PUT /Attachments/{documentId}/{documentType}
 * documentType 0 = IssuedInvoice
 * Body: multipart/form-data with the PDF file
 *
 * If reportPdf is not present, passes through without action.
 */
export default class IDokladUploadAttachmentToInvoiceConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const data = dto.getJsonData();

        if (data.reportPdf?.contentBase64) {
            const pdfBuffer = Buffer.from(data.reportPdf.contentBase64, 'base64');
            const filename = data.reportPdf.filename || 'report.pdf';
            const boundary = `----OrchestyBoundary${Date.now()}`;

            const bodyParts = [
                `--${boundary}\r\n`,
                `Content-Disposition: form-data; name="file"; filename="${filename}"\r\n`,
                'Content-Type: application/pdf\r\n\r\n',
            ];
            const header = Buffer.from(bodyParts.join(''));
            const footer = Buffer.from(`\r\n--${boundary}--\r\n`);
            const multipartBody = Buffer.concat([header, pdfBuffer, footer]);

            const applicationInstall = await this.getApplicationInstallFromProcess(dto);
            const baseRequest = await this.getApplication().getRequestDto(
                dto,
                applicationInstall,
                HttpMethods.PUT,
                `${BASE_URL}/Attachments/${data.iDokladInvoiceId}/${DOCUMENT_TYPE_ISSUED_INVOICE}`,
            );

            const headers = baseRequest.getHeaders();
            headers['Content-Type'] = `multipart/form-data; boundary=${boundary}`;
            delete headers['content-type'];

            const request = new RequestDto(
                `${BASE_URL}/Attachments/${data.iDokladInvoiceId}/${DOCUMENT_TYPE_ISSUED_INVOICE}`,
                HttpMethods.PUT,
                dto,
                multipartBody,
                headers,
            );

            await this.getSender().send(request, {
                success: [200],
                stopAndFail: '<500',
                repeat: '>=500',
            }, 60, 3);
        }

        return dto.setNewJsonData({
            invoiceId: data.invoiceId,
            iDokladInvoiceId: data.iDokladInvoiceId,
        });
    }

}
