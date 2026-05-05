import PipedriveApplication from '@orchesty/connector-pipedrive/dist/PipedriveApplication';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { ISalesFormContext } from '../../Sales/types';

export const NAME = 'pipedrive-add-sales-person';

interface IPipedriveContact {
    value: string;
    primary: boolean;
}

interface IPipedrivePersonRequestBody {
    name: string;
    email: IPipedriveContact[];
    phone?: IPipedriveContact[];
    job_title?: string;
    org_id?: number;
}

interface IPipedrivePersonResponse {
    success: boolean;
    data: {
        id: number;
        name: string;
    };
}

export default class PipedriveAddSalesPersonConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<ISalesFormContext>): Promise<ProcessDto<ISalesFormContext>> {
        const ctx = dto.getJsonData();
        checkParams(ctx as unknown as Record<string, unknown>, ['firstName', 'lastName', 'email']);

        const app = this.getApplication<PipedriveApplication>();
        const appInstall = await this.getApplicationInstallFromProcess(dto);

        const body: IPipedrivePersonRequestBody = {
            name: `${ctx.firstName} ${ctx.lastName}`,
            email: [{ value: ctx.email, primary: true }],
        };
        if (ctx.phone) {
            body.phone = [{ value: ctx.phone, primary: true }];
        }
        if (ctx.jobTitle) {
            body.job_title = ctx.jobTitle;
        }
        if (ctx.orgId) {
            body.org_id = ctx.orgId;
        }

        const request = app.getRequestDto(
            dto,
            appInstall,
            HttpMethods.POST,
            '/persons',
            body,
        );

        const response = await this.getSender().send(request, {
            success: [200, 201],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        const responseBody = response.getJsonBody() as IPipedrivePersonResponse;

        return dto.setNewJsonData<ISalesFormContext>({
            ...ctx,
            personId: responseBody.data.id,
        });
    }

}
