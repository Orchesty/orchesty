import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { NAME as BEECEPTOR_POST_CATEGORY_CONNECTOR } from '../../Beeceptor/Connector/BeeceptorPostCategoryConnector';
import { NAME as BEECEPTOR_PUT_CATEGORY_CONNECTOR } from '../../Beeceptor/Connector/BeeceptorPutCategoryConnector';
import { IOutput as IInput } from '../Batch/MySqlGetCategoryListBatch';
import { EntityType } from '../Entity/MySqlDocument';
import MySqlRepository from '../Repository/MySqlRepository';

export const NAME = 'mysql-category-find-id';

export const MYSQL_CATEGORY_ID = 'mysqlCategoryId';

export default class MySqlCategoryFindId extends ACommonNode {

    public constructor(private readonly repository: MySqlRepository) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { id: entityId } = dto.getJsonData();
        dto.addHeader(MYSQL_CATEGORY_ID, String(entityId));

        const category = await this.repository.findOne({ type: EntityType.CATEGORY, entityId });

        if (!category) {
            return dto.setForceFollowers(BEECEPTOR_POST_CATEGORY_CONNECTOR);
        }

        dto.setNewJsonData({
            ...dto.getJsonData(),
            id: category.externalId,
        });

        return dto.setForceFollowers(BEECEPTOR_PUT_CATEGORY_CONNECTOR);
    }

}
