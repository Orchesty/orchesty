import FlexiBeeApplication from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';

export const NAME = 'flexibee-upload-prijata-faktura-pdf';

export interface IInput {
    pdfBase64: string;
    invoiceCode: string;
    filename?: string;
}

export interface IOutput {
    invoiceCode: string;
    uploaded: boolean;
}

/**
 * Uploads a PDF attachment to a FlexiBee received invoice (faktura-prijata).
 *
 * API: PUT /{firma}/faktura-prijata/{code}/prilohy/new/{filename}
 * Content-Type: application/pdf
 * Body: raw PDF bytes
 *
 * Input:  { pdfBase64, invoiceCode, filename? }
 * Output: { invoiceCode, uploaded }
 */
export default class FlexiBeeUploadPrijataFakturaPdfConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const data = dto.getJsonData();
        checkParams(data as unknown as Record<string, unknown>, ['pdfBase64', 'invoiceCode']);

        const pdfBuffer = Buffer.from(data.pdfBase64, 'base64');
        const filename = data.filename ?? `faktura-${data.invoiceCode}.pdf`;

        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const application = this.getApplication<FlexiBeeApplication>();

        const url = application.getUrl(
            applicationInstall,
            `faktura-prijata/${data.invoiceCode}/prilohy/new/${filename}`,
        );

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

        return dto.setNewJsonData({
            invoiceCode: data.invoiceCode,
            uploaded: true,
        });
    }

}
