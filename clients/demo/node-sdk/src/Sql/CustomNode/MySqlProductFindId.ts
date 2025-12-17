import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { NAME as BEECEPTOR_POST_PRODUCT_CONNECTOR } from '../../Beeceptor/Connector/BeeceptorPostProductConnector';
import { NAME as BEECEPTOR_PUT_PRODUCT_CONNECTOR } from '../../Beeceptor/Connector/BeeceptorPutProductConnector';
import { IOutput as IInput } from '../Batch/MySqlGetProductListBatch';
import { EntityType } from '../Entity/MySqlDocument';
import MySqlRepository from '../Repository/MySqlRepository';

export const NAME = 'mysql-product-find-id';

export const MYSQL_PRODUCT_ID = 'mysqlProductId';

export default class MySqlProductFindId extends ACommonNode {

    public constructor(private readonly repository: MySqlRepository) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { id: entityId } = dto.getJsonData();
        dto.addHeader(MYSQL_PRODUCT_ID, String(entityId));

        const product = await this.repository.findOne({ type: EntityType.PRODUCT, entityId });

        if (!product) {
            return dto.setForceFollowers(BEECEPTOR_POST_PRODUCT_CONNECTOR);
        }

        dto.setNewJsonData({
            ...dto.getJsonData(),
            id: product.externalId,
        });

        return dto.setForceFollowers(BEECEPTOR_PUT_PRODUCT_CONNECTOR);
    }

}
