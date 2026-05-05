import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import FlexiBeeCreatePartnerConnector from './FlexiBeeCreatePartnerConnector';

export const NAME = 'flexibee-create-partner-from-invoice';

/* eslint-disable @typescript-eslint/naming-convention */
interface IPartnerAddress {
    NickName?: string;
    Firstname?: string;
    Surname?: string;
    Street?: string;
    City?: string;
    PostalCode?: string;
    CountryId?: number;
    IdentificationNumber?: string;
    VatIdentificationNumber?: string;
    Email?: string;
    Phone?: string;
}
/* eslint-enable @typescript-eslint/naming-convention */

/**
 * Maps iDoklad CountryId to FlexiBee country code.
 * iDoklad: 1 = SK, 2 = CZ. For unknown IDs, falls back to empty string
 * (FlexiBee will use its default).
 */
function mapCountryToFlexiCode(countryId?: number): string {
    switch (countryId) {
        case 1: return 'code:SK';
        case 2: return 'code:CZ';
        case undefined: return '';
        default: return '';
    }
}

/**
 * Derived connector: creates a FlexiBee partner from an iDoklad invoice
 * payload, but only if flexiPartnerCode is null (partner not found).
 * If flexiPartnerCode is already set, passes through without API call.
 *
 * Input:  { ...invoice, flexiPartnerCode: string | null }
 * Output: { ...invoice, flexiPartnerCode: 'code:IDOKLAD-{ico}' }
 */
export default class FlexiBeeCreatePartnerFromInvoice extends FlexiBeeCreatePartnerConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;

        // Partner already exists — pass through
        if (data.flexiPartnerCode) {
            return dto;
        }

        const partnerAddress = data.PartnerAddress as IPartnerAddress | undefined;
        const ic = partnerAddress?.IdentificationNumber;

        if (!ic) {
            // No IČO and no existing partner — pass through without binding
            return dto;
        }

        const partnerCode = `IDOKLAD-${ic}`;
        const fullName = [partnerAddress?.Firstname, partnerAddress?.Surname].filter(Boolean).join(' ');
        // eslint-disable-next-line @typescript-eslint/prefer-nullish-coalescing
        const nazev = partnerAddress?.NickName?.trim() || fullName || ic;

        const countryId = partnerAddress?.CountryId;
        const stat = mapCountryToFlexiCode(countryId);

        // Reshape dto for the base connector
        dto.setJsonData({
            kod: partnerCode,
            nazev,
            ulice: partnerAddress?.Street ?? '',
            mesto: partnerAddress?.City ?? '',
            psc: partnerAddress?.PostalCode ?? '',
            stat,
            ic,
            dic: partnerAddress?.VatIdentificationNumber ?? '',
            email: partnerAddress?.Email ?? '',
            tel: partnerAddress?.Phone ?? '',
        });

        await super.processAction(dto);

        // Restore invoice payload with the new partner code
        dto.setJsonData({ ...data, flexiPartnerCode: `code:${partnerCode}` });
        return dto;
    }

}
