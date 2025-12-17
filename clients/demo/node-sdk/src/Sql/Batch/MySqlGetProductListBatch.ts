import ASqlBatchConnector from '@orchesty/connector-sql/dist/Common/ASqlBatchConnector';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { AUDIT_ENTITY } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import { WebhookType } from '../../Beeceptor/BeeceptorApplication';

const LAST_RUN = 'lastRun';
const BATCH_SIZE = 100;

export default class MySqlGetProductListBatch extends ASqlBatchConnector {

    protected name = 'get-product-list';

    protected async processResult(res: IResult, dto: BatchProcessDto): Promise<BatchProcessDto> {
        const appInstall = await this.getApplicationInstallFromProcess(dto);
        appInstall.addNonEncryptedSettings({
            [LAST_RUN]: {
                product: new Date().toISOString(),
            },
        });

        await this.getDbClient().getApplicationRepository().update(appInstall);

        const { rows } = res;

        if (rows.length >= BATCH_SIZE) {
            const offset = Number(dto.getBatchCursor());
            dto.setBatchCursor(String(offset + BATCH_SIZE));
        }

        for (const row of rows) {
            dto.addItem(row, undefined, undefined, {
                [AUDIT_ENTITY]: JSON.stringify({ product: { key: 'id', fields: [{ id: String(row.id) }] } }),
            });
        }

        return dto;
    }

    protected async getQuery(processDto: BatchProcessDto<IInput>): Promise<string> {
        const appInstall = await this.getApplicationInstallFromProcess(processDto);

        const lastRun: string = appInstall.getNonEncryptedSettings()[LAST_RUN]?.product;
        const { event, ids } = processDto.getJsonData();

        // eslint-disable-next-line no-nested-ternary
        const where = [WebhookType.PRODUCT_CREATED, WebhookType.PRODUCT_UPDATED].includes(event)
            ? ` WHERE id IN (${ids.join(',')})`
            : lastRun
                ? ` WHERE updated > '${lastRun}'`
                : '';

        const offset = processDto.getBatchCursor('0');

        return `SELECT id, name FROM product${where} LIMIT ${BATCH_SIZE} OFFSET ${offset}`;
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
    name: string;
}
