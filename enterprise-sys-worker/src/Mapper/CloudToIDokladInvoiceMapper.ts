import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'cloud-to-idoklad-invoice-mapper';

/**
 * Maps the Cloud invoice payload to iDoklad IssuedInvoice format.
 *
 * Input:  { invoiceId, companyId, subject, duzp, currency, items, partnerId }
 * Output: { cloudInvoiceId, invoiceData: { PartnerId, Description, DateOfTaxing, Items, ... } }
 */
export default class CloudToIDokladInvoiceMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto): ProcessDto {
        const data = dto.getJsonData() as ICloudInvoicePayload;

        // PriceType: 0 = s DPH, 1 = bez DPH, 2 = přenesená daňová povinnost
        const priceType = data.reverseChargeVat ? 2 : 1;

        const invoiceData: Record<string, unknown> = {
            PartnerId: data.partnerId,
            Description: data.subject,
            DateOfTaxing: data.duzp,
            Items: (data.items ?? []).map((item) => ({
                Name: item.description,
                Amount: item.quantity,
                UnitPrice: item.unitPrice,
                PriceType: priceType,
                VatRateType: 1,             // 1 = základní sazba (21%)
                Unit: 'ks',
                DiscountPercentage: 0,
                IsTaxMovement: !data.reverseChargeVat,
            })),
        };

        dto.setJsonData({
            cloudInvoiceId: data.invoiceId,
            maturityDays: data.maturityDays,
            invoiceData,
            reportPdf: data.reportPdf,
        });

        return dto;
    }

}

interface ICloudInvoicePayload {
    invoiceId: string;
    companyId: string;
    subject: string;
    duzp: string;
    currency: string;
    reverseChargeVat: boolean;
    maturityDays?: number;
    items: {
        description: string;
        quantity: number;
        unitPrice: number;
        totalPrice: number;
    }[];
    partnerId: number;
    reportPdf?: {
        filename: string;
        contentBase64: string;
    };
}
