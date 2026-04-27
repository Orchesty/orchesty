import { NAME as BEECEPTOR_APP_NAME } from '@orchesty/connector-beeceptor/dist/BeeceptorApplication';
import AuditCheckpointRoleEnum from '@orchesty/nodejs-sdk/dist/lib/Commons/AuditCheckpointRoleEnum';
import { IAuditCheckpoint } from '@orchesty/nodejs-sdk/dist/lib/Commons/IAuditCheckpoint';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IOutput as IInput } from '../../Sql/CustomNode/MySqlProductCategoryFindId';

export const NAME = `${BEECEPTOR_APP_NAME}-put-product-categories-connector`;

export default class BeeceptorPutProductCategoriesConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public getAuditCheckpoint(): IAuditCheckpoint {
        return {
            role: AuditCheckpointRoleEnum.PROCESS_EXIT,
            fields: ['id', 'ids'],
        };
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { id, ids } = dto.getJsonData();
        const request = await this.getApplication().getRequestDto(
            dto,
            await this.getApplicationInstallFromProcess(dto),
            HttpMethods.PUT,
            `/api/products/${id}/categories`,
            { ids },
        );

        await this.getSender().send(request, [204]);

        return dto;
    }

}
