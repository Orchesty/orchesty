import { NAME as BEECEPTOR_APP_NAME } from '@orchesty/connector-beeceptor/dist/BeeceptorApplication';
import AuditCheckpointRoleEnum from '@orchesty/nodejs-sdk/dist/lib/Commons/AuditCheckpointRoleEnum';
import { IAuditCheckpoint } from '@orchesty/nodejs-sdk/dist/lib/Commons/IAuditCheckpoint';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IOutput as IInput } from '../../Sql/Batch/MySqlGetCategoryListBatch';

export const NAME = `${BEECEPTOR_APP_NAME}-post-category-connector`;

export default class BeeceptorPostCategoryConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public getAuditCheckpoint(): IAuditCheckpoint {
        return {
            role: AuditCheckpointRoleEnum.PROCESS_EXIT,
            fields: ['id', 'name'],
        };
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { id, name } = dto.getJsonData();
        const request = await this.getApplication().getRequestDto(
            dto,
            await this.getApplicationInstallFromProcess(dto),
            HttpMethods.POST,
            '/api/categories',
            { name },
        );

        const response = (await this.getSender().send<IOutput>(request)).getJsonBody();

        return dto
            .setNewJsonData(response)
            .addAuditHeader('category', 'id', [{ id: String(id), externalId: response.id }]);
    }

}

export interface IOutput {
    id: string;
}
