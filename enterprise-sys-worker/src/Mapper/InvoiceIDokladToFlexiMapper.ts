import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'invoice-idoklad-to-flexi-mapper';

export default class InvoiceIDokladToFlexiMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto): ProcessDto {
        const invoice = dto.getJsonData() as IIDokladInvoice;

        const flexiInvoice: Record<string, unknown> = {
            typDokl: 'code:FAKTURA',
            kod: `IDOKLAD-${invoice.DocumentNumber ?? invoice.Id}`,
            datVyst: invoice.DateOfIssue,
            datSplat: invoice.DateOfMaturity,
            datUcto: invoice.DateOfTaxing,
            popis: invoice.Description,
            cisDos662: invoice.DocumentNumber ?? '',
            poznam: invoice.Note ?? '',
            varSym: invoice.VariableSymbol ?? '',
        };

        // Link to partner in FlexiBee address book (set by FlexiBeeEnsurePartnerConnector)
        if (invoice.flexiPartnerCode) {
            flexiInvoice.firma = invoice.flexiPartnerCode;
        }

        // Detect if invoice has intra-EU reverse charge items (PriceType 2)
        const isIntraEu = invoice.Items?.some((item) => item.PriceType === 2) ?? false;

        // Map invoice items to FlexiBee polozkyFaktury
        if (invoice.Items && invoice.Items.length > 0) {
            flexiInvoice.polozkyFaktury = invoice.Items.map((item) => ({
                nazev: item.Name,
                mnozMj: item.Amount,
                cenaMj: item.Prices.UnitPrice,
                typCenyDphK: mapPriceType(item.PriceType),
                typSzbDphK: item.PriceType === 2
                    ? 'typSzbDph.dphOsv'                   // intra-EU: osvobozeno (0%)
                    : mapVatRateType(item.VatRateType),
                jednotka: item.Unit ?? 'ks',
                slevaPol: item.DiscountPercentage ?? 0,
            }));
        }

        // Intra-EU services: set DPH row 26 (§ 24a — plnění s místem plnění mimo tuzemsko)
        if (isIntraEu) {
            flexiInvoice.clenDph = 'code:26';
        }

        // Preserve iDoklad metadata for tagging after sync
        const output = {
            flexiInvoice,
            idokladInvoiceId: invoice.Id,
            idokladTags: invoice.Tags ?? [],
        };

        dto.setJsonData(output);

        return dto;
    }

}

function mapPriceType(priceType: number): string {
    switch (priceType) {
        case 0: return 'typCeny.sDph';        // s DPH
        case 1: return 'typCeny.bezDph';       // bez DPH
        case 2: return 'typCeny.bezDph';       // přenesená daňová povinnost → bez DPH
        default: return 'typCeny.bezDph';
    }
}

function mapVatRateType(vatRateType: number): string {
    switch (vatRateType) {
        case 0: return 'typSzbDph.dphSniz';
        case 1: return 'typSzbDph.dphZakl';
        case 2: return 'typSzbDph.dphOsv';
        case 3: return 'typSzbDph.dphSniz2';
        default: return 'typSzbDph.dphZakl';
    }
}

interface IIDokladInvoice {
    Id: number;
    Description: string;
    DateOfIssue: string;
    DateOfMaturity: string;
    DateOfTaxing: string;
    DocumentNumber?: string;
    Note?: string;
    VariableSymbol?: string;
    VatReverseChargeCodeId?: number | null;
    Tags?: { TagId: number }[];
    flexiPartnerCode?: string;
    Items: IIDokladInvoiceItem[];
}

interface IIDokladInvoiceItem {
    Name: string;
    Amount: number;
    Prices: {
        UnitPrice: number;
        TotalWithVat: number;
        TotalWithoutVat: number;
        TotalVat: number;
    };
    PriceType: number;
    VatRateType: number;
    VatRate?: number;
    IsTaxMovement?: boolean;
    Unit?: string;
    DiscountPercentage?: number;
}
