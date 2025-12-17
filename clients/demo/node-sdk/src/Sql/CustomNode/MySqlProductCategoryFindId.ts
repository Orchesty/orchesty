import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { IOutput as IInput } from '../Batch/MySqlGetProductCategoryListBatch';
import { EntityType } from '../Entity/MySqlDocument';
import MySqlRepository from '../Repository/MySqlRepository';

export const NAME = 'mysql-product-category-find-id';

export const MYSQL_PRODUCT_ID = 'mysqlProductId';

export default class MySqlProductCategoryFindId extends ACommonNode {

    public constructor(private readonly repository: MySqlRepository) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { id: entityId, categories } = dto.getJsonData();

        const productCategory = await this.repository.findOne({ type: EntityType.PRODUCT, entityId });

        if (!productCategory) {
            return dto.setStopProcess(ResultCode.DO_NOT_CONTINUE, 'Unknown product category');
        }

        const ids = (await this.repository.findMany({
            type: EntityType.CATEGORY,
            entityId: { $in: categories },
        })).map((category) => category.externalId);

        return dto.setNewJsonData({
            id: productCategory.externalId,
            ids,
        });
    }

}

export interface IOutput {
    id: string;
    ids: string[];
}
