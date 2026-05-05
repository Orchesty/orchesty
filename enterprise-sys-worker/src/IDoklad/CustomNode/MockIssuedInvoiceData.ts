import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'mock-issued-invoice-data';

export default class MockIssuedInvoiceData extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto): ProcessDto {
        dto.setJsonData({
            PartnerId: 19962643,
            Description: 'Faktura za konzultační služby - TEST',
            DateOfIssue: '2026-02-07T00:00:00',
            DateOfMaturity: '2026-02-21T00:00:00',
            DateOfTaxing: '2026-02-07T00:00:00',
            PaymentOptionId: 1,
            IsEet: false,
            IsIncomeTax: true,
            OrderNumber: 'ORD-2026-001',
            Note: 'Testovací faktura z Orchesty',
            Items: [
                {
                    Name: 'Konzultační služby',
                    Amount: 10.0,
                    UnitPrice: 1500.00,
                    PriceType: 1,
                    VatRateType: 1,
                    IsTaxMovement: false,
                    DiscountPercentage: 0.0,
                    Unit: 'hod',
                },
                {
                    Name: 'Cestovné',
                    Amount: 1.0,
                    UnitPrice: 500.00,
                    PriceType: 1,
                    VatRateType: 1,
                    IsTaxMovement: false,
                    DiscountPercentage: 0.0,
                    Unit: 'ks',
                },
            ],
        });

        return dto;
    }

}
