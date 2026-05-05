import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';

export const NAME = 'filter-synced-invoices';

/**
 * Tag ID used to mark invoices that have been successfully synced to FlexiBee.
 * Invoices with this tag are filtered out (skipped).
 */
const SYNCED_TAG_ID = 56491;

export default class FilterSyncedInvoices extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto): ProcessDto {
        const invoice = dto.getJsonData() as Record<string, unknown>;
        // eslint-disable-next-line @typescript-eslint/naming-convention
        const tags = (invoice.Tags ?? []) as { TagId: number }[];

        if (tags.some((t) => t.TagId === SYNCED_TAG_ID)) {
            dto.setStopProcess(ResultCode.DO_NOT_CONTINUE, `Invoice already synced (has tag ${SYNCED_TAG_ID}), skipping.`);
        }

        return dto;
    }

}
