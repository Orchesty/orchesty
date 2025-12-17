import { NAME as BEECEPTOR_APP_NAME } from '@orchesty/connector-beeceptor/dist/BeeceptorApplication';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IOutput as IInput } from '../../Sql/Batch/MySqlGetProductListBatch';

export const NAME = `${BEECEPTOR_APP_NAME}-post-product-connector`;

export default class BeeceptorPostProductConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { id, name } = dto.getJsonData();
        const request = await this.getApplication().getRequestDto(
            dto,
            await this.getApplicationInstallFromProcess(dto),
            HttpMethods.POST,
            '/api/products',
            { name },
        );

        const response = (await this.getSender().send<IOutput>(request)).getJsonBody();

        return dto
            .setNewJsonData(response)
            .addAuditHeader('product', 'id', [{ id: String(id), externalId: response.id }]);
    }

}

export interface IOutput {
    id: string;
}
