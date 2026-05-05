import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import IDokladPrepareIssuedInvoiceConnector from './IDokladPrepareIssuedInvoiceConnector';

export const NAME = 'i-doklad-prepare-invoice-from-cloud';

/**
 * Derived connector: extracts invoiceData from the mapper output,
 * calls the base connector to fetch defaults and merge,
 * then wraps the result back with cloudInvoiceId.
 *
 * Input:  { cloudInvoiceId, invoiceData }
 * Output: { cloudInvoiceId, invoiceData: <merged with defaults> }
 */
export default class IDokladPrepareInvoiceFromCloud extends IDokladPrepareIssuedInvoiceConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;
        const cloudInvoiceId = data.cloudInvoiceId as string;
        const maturityDays = data.maturityDays as number | undefined;
        const invoiceData = data.invoiceData as Record<string, unknown>;
        const { reportPdf } = data;

        // Reshape dto for the base connector (expects flat invoice data)
        dto.setJsonData(invoiceData);
        await super.processAction(dto);

        // Read the merged result (defaults + custom overrides)
        const mergedInvoice = dto.getJsonData() as Record<string, unknown>;

        // Recalculate DateOfMaturity from DateOfIssue (not from DUZP).
        // maturityDays is required from Cloud payload.
        if (maturityDays === undefined || maturityDays === null) {
            throw new Error('Missing required field [maturityDays] in Cloud payload');
        }
        const dateOfIssue = new Date(mergedInvoice.DateOfIssue as string);
        const days = maturityDays;
        const maturity = new Date(dateOfIssue);
        maturity.setDate(maturity.getDate() + days);
        mergedInvoice.DateOfMaturity = maturity.toISOString();

        dto.setJsonData({
            cloudInvoiceId,
            invoiceData: mergedInvoice,
            reportPdf,
        });

        return dto;
    }

}
