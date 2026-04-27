import ASqlBatchConnector from '@orchesty/connector-sql/dist/Common/ASqlBatchConnector';
import AuditCheckpointRoleEnum from '@orchesty/nodejs-sdk/dist/lib/Commons/AuditCheckpointRoleEnum';
import { IAuditCheckpoint } from '@orchesty/nodejs-sdk/dist/lib/Commons/IAuditCheckpoint';
import { AuditData } from '@orchesty/nodejs-sdk/dist/lib/Utils/AProcessDto';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { WebhookType } from '../../Beeceptor/BeeceptorApplication';

const LAST_RUN = 'lastRun';
const BATCH_SIZE = 100;

export default class MySqlGetProductCategoryListBatch extends ASqlBatchConnector {

    protected name = 'get-product-category-list';

    public getAuditCheckpoint(): IAuditCheckpoint {
        return {
            role: AuditCheckpointRoleEnum.PROCESS_ENTRY,
            fields: ['id', 'categories'],
        };
    }

    protected async processResult(res: IResult, dto: BatchProcessDto): Promise<BatchProcessDto> {
        const appInstall = await this.getApplicationInstallFromProcess(dto);
        appInstall.addNonEncryptedSettings({
            [LAST_RUN]: {
                productCategory: new Date().toISOString(),
            },
        });

        await this.getDbClient().getApplicationRepository().update(appInstall);

        const { rows } = res;

        if (rows.length >= BATCH_SIZE) {
            const offset = Number(dto.getBatchCursor());
            dto.setBatchCursor(String(offset + BATCH_SIZE));
        }

        for (const row of rows) {
            const audits: AuditData = {
                product: { key: 'id', fields: [{ id: String(row.id) }] },
                category: { key: 'id', fields: row.categories.map((c) => ({ id: String(c) })) },
            };
            dto.addItemWithAudit(row, audits);
        }

        return dto;
    }

    protected async getQuery(processDto: BatchProcessDto<IInput>): Promise<string> {
        const appInstall = await this.getApplicationInstallFromProcess(processDto);

        const lastRun: string = appInstall.getNonEncryptedSettings()[LAST_RUN]?.productCategory;
        const { event, ids } = processDto.getJsonData();

        // eslint-disable-next-line no-nested-ternary
        const where = event === WebhookType.PRODUCT_CATEGORY_UPDATED
            ? ` WHERE pc.id IN (${ids.join(',')})`
            : lastRun
                ? ` WHERE p.category_updated > '${lastRun}'`
                : '';

        const offset = processDto.getBatchCursor('0');

        return `SELECT p.id, json_arrayagg(pc.category_id) AS categories FROM product_category pc JOIN product p ON pc.product_id = p.id${where} GROUP BY p.id LIMIT ${BATCH_SIZE} OFFSET ${offset}`;
    }

}
export interface IInput {
    event: WebhookType;
    ids: string[];
}

export interface IResult {
    rows: IOutput[];
}

export interface IOutput {
    id: number;
    categories: number[];
}
