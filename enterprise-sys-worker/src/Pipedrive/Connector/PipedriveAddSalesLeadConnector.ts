import { SUBDOMAIN } from '@orchesty/connector-pipedrive/dist/PipedriveApplication';
import PipedriveAddLeadConnector, { IInput, IOutput } from '@orchesty/connector-pipedrive/dist/Connector/PipedriveAddLeadConnector';
import CoreFormsEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { ISalesFormContext } from '../../Sales/types';

export const NAME = 'pipedrive-add-sales-lead';

interface ISalesLeadRequestBody extends IInput {
    origin_id?: string;
}

function buildLeadUrl(subdomain: string, leadId: string): string {
    return `https://${subdomain}.pipedrive.com/leads/inbox/${leadId}`;
}

export default class PipedriveAddSalesLeadConnector extends PipedriveAddLeadConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const ctxDto = dto as unknown as ProcessDto<ISalesFormContext>;
        const ctx = ctxDto.getJsonData();
        checkParams(
            ctx as unknown as Record<string, unknown>,
            ['firstName', 'lastName', 'company', 'personId', 'orgId'],
        );

        const appInstall = await this.getApplicationInstallFromProcess(dto);
        const subdomain = appInstall.getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM]?.[SUBDOMAIN] as string | undefined;

        const leadInput: ISalesLeadRequestBody = {
            title: `${ctx.firstName} ${ctx.lastName} (${ctx.company})`,
            person_id: ctx.personId as number,
            organization_id: ctx.orgId as number,
            origin_id: ctx.source,
        };

        dto.setNewJsonData<ISalesLeadRequestBody>(leadInput);
        await super.processAction(dto);

        const leadOutput = dto.getJsonData() as unknown as IOutput;

        return ctxDto.setNewJsonData<ISalesFormContext>({
            ...ctx,
            leadId: leadOutput.id,
            leadUrl: subdomain ? buildLeadUrl(subdomain, leadOutput.id) : undefined,
        }) as unknown as ProcessDto<IOutput>;
    }

}
