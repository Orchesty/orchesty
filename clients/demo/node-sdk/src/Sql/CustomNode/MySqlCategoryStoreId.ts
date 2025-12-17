import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IOutput as IInput } from '../../Beeceptor/Connector/BeeceptorPostCategoryConnector';
import { EntityType } from '../Entity/MySqlDocument';
import MySqlRepository from '../Repository/MySqlRepository';
import { MYSQL_CATEGORY_ID } from './MySqlCategoryFindId';

export const NAME = 'mysql-category-store-id';

export default class MySqlCategoryStoreId extends ACommonNode {

    public constructor(private readonly repository: MySqlRepository) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { id: externalId } = dto.getJsonData();
        const entityId = Number(dto.getHeader(MYSQL_CATEGORY_ID));

        await this.repository.insert({
            id: '',
            type: EntityType.CATEGORY,
            entityId,
            externalId,
        });

        return dto;
    }

}
