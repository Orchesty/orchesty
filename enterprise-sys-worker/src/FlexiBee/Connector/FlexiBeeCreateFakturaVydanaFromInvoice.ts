import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import FlexiBeeCreateFakturaVydanaConnector from './FlexiBeeCreateFakturaVydanaConnector';

export const NAME = 'flexibee-create-faktura-vydana-from-invoice';

const SYNCED_TAG_ID = 56491;

/**
 * Derived connector: extracts flexiInvoice from the mapper output,
 * calls the base connector to create the invoice in FlexiBee,
 * then prepares the payload for the PDF download + tag connectors.
 *
 * Input:  { flexiInvoice, idokladInvoiceId, idokladTags }
 * Output: { invoiceId, flexiInvoiceCode, documentNumber, tagId, existingTags }
 */
export default class FlexiBeeCreateFakturaVydanaFromInvoice extends FlexiBeeCreateFakturaVydanaConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;
        const flexiInvoice = data.flexiInvoice as Record<string, unknown>;
        const idokladInvoiceId = data.idokladInvoiceId as number;
        const idokladTags = (data.idokladTags ?? []) as { TagId: number }[];

        // Reshape dto for the base connector (expects raw invoice data)
        dto.setJsonData(flexiInvoice);
        await super.processAction(dto);

        // Extract FlexiBee invoice ID from the create response
        const response = JSON.parse(dto.getData()) as IFlexiBeeCreateResponse;
        const flexiInvoiceId = response?.winstrom?.results?.[0]?.id;

        // Prepare payload for PDF download + tag connectors
        dto.setJsonData({
            invoiceId: idokladInvoiceId,
            flexiInvoiceCode: flexiInvoiceId ? String(flexiInvoiceId) : undefined,
            documentNumber: flexiInvoice.varSym ?? flexiInvoice.kod ?? String(idokladInvoiceId),
            tagId: SYNCED_TAG_ID,
            existingTags: idokladTags.map((t) => t.TagId),
        });

        return dto;
    }

}

interface IFlexiBeeCreateResponse {
    winstrom?: {
        results?: { id?: string | number; ref?: string }[];
    };
}
