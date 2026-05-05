import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { BASE_URL } from '../IDokladClientCredentialsApplication';

export const NAME = 'i-doklad-list-issued-invoices';

const PAGE_SIZE = 50;

export default class IDokladListIssuedInvoicesBatch extends ABatchNode {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: BatchProcessDto): Promise<BatchProcessDto> {
        const page = Number(dto.getBatchCursor('1'));
        const { filters } = dto.getJsonData() as { filters?: string[] };

        let url = `${BASE_URL}/IssuedInvoices?page=${page}&pageSize=${PAGE_SIZE}&sort=DateOfIssue~desc`;

        if (filters?.length) {
            url += `&filter=${filters.map((f) => encodeURIComponent(f)).join('|')}`;
        }

        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const request = await this.getApplication().getRequestDto(
            dto,
            applicationInstall,
            HttpMethods.GET,
            url,
        );

        const response = await this.getSender().send(request, {
            success: [200],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);
        const body = JSON.parse(response.getBody()) as IIssuedInvoiceListResponse;

        const items = body.Data?.Items ?? [];
        const totalPages = Math.ceil((body.Data?.TotalItems ?? 0) / PAGE_SIZE);

        if (page < totalPages) {
            dto.setBatchCursor(String(page + 1));
        } else {
            dto.removeBatchCursor();
        }

        return dto.setItemList(items);
    }

}

/* eslint-disable @typescript-eslint/naming-convention */
interface IIssuedInvoiceListResponse {
    Data: {
        Items: Record<string, unknown>[];
        TotalItems: number;
        TotalPages: number;
    };
    IsSuccess: boolean;
    StatusCode: number;
    Message: string | null;
}
/* eslint-enable @typescript-eslint/naming-convention */
