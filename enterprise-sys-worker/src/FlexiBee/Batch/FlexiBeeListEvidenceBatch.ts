import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import FlexiBeeApplication from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';

const PAGE_SIZE = 100;

/**
 * Generic batch that fetches records from a FlexiBee evidence page by page.
 * Each invocation makes exactly one API call (one page).
 *
 * Output modes (controlled by `decompose` constructor parameter):
 *   - false (default): entire page sent as a single message { items: [...] }
 *     → use for Wflow register PATCH (bulk array)
 *   - true: page decomposed into individual items via setItemList()
 *     → use for Wflow document types PUT (one item per call)
 *
 * Constructor takes the evidence name (e.g. 'stredisko') and a unique node name.
 */
export default class FlexiBeeListEvidenceBatch extends ABatchNode {

    constructor(
        private readonly evidence: string,
        private readonly nodeName: string,
        private readonly decompose: boolean = false,
    ) {
        super();
    }

    public getName(): string {
        return this.nodeName;
    }

    public async processAction(dto: BatchProcessDto): Promise<BatchProcessDto> {
        const page = Number(dto.getBatchCursor('0'));
        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const application = this.getApplication<FlexiBeeApplication>();

        const url = application.getUrl(
            applicationInstall,
            `${this.evidence}.json?detail=full&add-row-count=true&start=${page * PAGE_SIZE}&limit=${PAGE_SIZE}`,
        );

        const request = await application.getRequestDto(
            dto, applicationInstall, HttpMethods.GET, url,
        );

        const response = await this.getSender().send(request, {
            success: [200],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        const data = JSON.parse(response.getBody()) as {
            winstrom: Record<string, unknown>;
        };

        const items = (data.winstrom[this.evidence] ?? []) as Record<string, unknown>[];
        const totalRows = Number(data.winstrom['@rowCount'] ?? 0);
        const totalPages = Math.ceil(totalRows / PAGE_SIZE);

        if (page + 1 < totalPages) {
            dto.setBatchCursor(String(page + 1));
        } else {
            dto.removeBatchCursor();
        }

        if (this.decompose) {
            dto.setItemList(items);
        } else {
            dto.addItem({ items });
        }
        return dto;
    }

}
