import { NAME as BEECEPTOR_APP_NAME } from '@orchesty/connector-beeceptor/dist/BeeceptorApplication';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IOutput as IInput } from '../../Sql/Batch/MySqlGetProductListBatch';

export const NAME = `${BEECEPTOR_APP_NAME}-put-product-connector`;

export default class BeeceptorPutProductConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { id, name } = dto.getJsonData();
        const request = await this.getApplication().getRequestDto(
            dto,
            await this.getApplicationInstallFromProcess(dto),
            HttpMethods.PUT,
            `/api/products/${id}`,
            { name },
        );

        await this.getSender().send(request, [204]);

        return dto;
    }

}
