import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'prepare-invoice-filters';

export default class PrepareInvoiceFilters extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto): ProcessDto {
        const currentYear = new Date().getFullYear();
        const filters: string[] = [
            `DateOfIssue~gte~${currentYear}-01-01`,
        ];

        dto.setJsonData({ filters });

        return dto;
    }

}
