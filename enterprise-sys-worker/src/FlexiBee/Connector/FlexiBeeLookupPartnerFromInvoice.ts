import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import FlexiBeeGetPartnerConnector from './FlexiBeeGetPartnerConnector';

export const NAME = 'flexibee-lookup-partner-from-invoice';

/**
 * Derived connector: extracts IČO from an iDoklad invoice payload,
 * looks up the partner in FlexiBee, and merges the result back.
 *
 * Input:  iDoklad invoice (with PartnerAddress.IdentificationNumber)
 * Output: { ...invoice, flexiPartnerCode: 'code:...' | null }
 */
export default class FlexiBeeLookupPartnerFromInvoice extends FlexiBeeGetPartnerConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const invoice = dto.getJsonData() as Record<string, unknown>;
        const partnerAddress = invoice.PartnerAddress as Record<string, unknown> | undefined;
        const ic = partnerAddress?.IdentificationNumber as string | undefined;

        if (!ic) {
            dto.setJsonData({ ...invoice, flexiPartnerCode: null });
            return dto;
        }

        // Reshape dto for the base connector
        dto.setJsonData({ ic });
        await super.processAction(dto);

        // Parse the FlexiBee response
        const response = JSON.parse(dto.getData()) as IFlexiBeeListResponse;
        const existing = response?.winstrom?.adresar;

        let flexiPartnerCode: string | null = null;
        if (existing && existing.length > 0) {
            const code = existing[0].kod ?? existing[0].id;
            flexiPartnerCode = `code:${code}`;
        }

        dto.setJsonData({ ...invoice, flexiPartnerCode });
        return dto;
    }

}

interface IFlexiBeeListResponse {
    winstrom?: {
        adresar?: { id: string; kod: string }[];
    };
}
