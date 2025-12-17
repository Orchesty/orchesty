import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IOutput as IInput } from '../../Beeceptor/Connector/BeeceptorPostProductConnector';
import { EntityType } from '../Entity/MySqlDocument';
import MySqlRepository from '../Repository/MySqlRepository';
import { MYSQL_PRODUCT_ID } from './MySqlProductFindId';

export const NAME = 'mysql-product-store-id';

export default class MySqlProductStoreId extends ACommonNode {

    public constructor(private readonly repository: MySqlRepository) {
        super();
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { id: externalId } = dto.getJsonData();
        const entityId = Number(dto.getHeader(MYSQL_PRODUCT_ID));

        await this.repository.insert({
            id: '',
            type: EntityType.PRODUCT,
            entityId,
            externalId,
        });

        return dto;
    }

}
