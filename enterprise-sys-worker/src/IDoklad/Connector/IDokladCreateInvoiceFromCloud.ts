import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import IDokladCreateIssuedInvoiceConnector from './IDokladCreateIssuedInvoiceConnector';

export const NAME = 'i-doklad-create-invoice-from-cloud';

/**
 * Derived connector: extracts invoiceData from the envelope,
 * calls the base connector to create the invoice in iDoklad,
 * then builds the callback payload.
 *
 * Input:  { cloudInvoiceId, invoiceData }
 * Output: { invoiceId, iDokladInvoiceId }
 */
export default class IDokladCreateInvoiceFromCloud extends IDokladCreateIssuedInvoiceConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;
        const cloudInvoiceId = data.cloudInvoiceId as string;
        const invoiceData = data.invoiceData as Record<string, unknown>;
        const reportPdf = data.reportPdf;

        // Reshape dto for the base connector (expects flat invoice data)
        dto.setJsonData(invoiceData);
        await super.processAction(dto);

        // Parse the iDoklad response to extract the new invoice ID
        const response = JSON.parse(dto.getData()) as ICreateInvoiceResponse;
        const iDokladInvoiceId = response?.Data?.Id;

        dto.setJsonData({
            invoiceId: cloudInvoiceId,
            iDokladInvoiceId: String(iDokladInvoiceId),
            reportPdf,
        });

        return dto;
    }

}

interface ICreateInvoiceResponse {
    Data?: {
        Id?: number;
    };
}
