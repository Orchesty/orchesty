import PipedriveApplication from '@orchesty/connector-pipedrive/dist/PipedriveApplication';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { ISalesFormContext } from '../../Sales/types';

export const NAME = 'pipedrive-add-sales-organization';

interface IPipedriveOrganizationResponse {
    success: boolean;
    data: {
        id: number;
        name: string;
    };
}

export default class PipedriveAddSalesOrganizationConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<ISalesFormContext>): Promise<ProcessDto<ISalesFormContext>> {
        const ctx = dto.getJsonData();
        checkParams(ctx as unknown as Record<string, unknown>, ['company']);

        const app = this.getApplication<PipedriveApplication>();
        const appInstall = await this.getApplicationInstallFromProcess(dto);

        const request = app.getRequestDto(
            dto,
            appInstall,
            HttpMethods.POST,
            '/organizations',
            { name: ctx.company },
        );

        const response = await this.getSender().send(request, {
            success: [200, 201],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        const body = response.getJsonBody() as IPipedriveOrganizationResponse;

        return dto.setNewJsonData<ISalesFormContext>({
            ...ctx,
            orgId: body.data.id,
        });
    }

}
