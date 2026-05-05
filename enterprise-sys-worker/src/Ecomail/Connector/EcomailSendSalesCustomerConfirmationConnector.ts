import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { ISalesFormContext } from '../../Sales/types';
import { buildSalesMergeVars } from '../../Sales/mergeVars';
import {
    FROM_EMAIL,
    FROM_NAME,
    SALES_CUSTOMER_TEMPLATE_ID,
    SETTINGS_FORM,
} from '../EcomailApplication';
import EcomailSendTransactionalEmailConnector, {
    IInput,
    IOutput,
} from './EcomailSendTransactionalEmailConnector';

export const NAME = 'ecomail-send-sales-customer-confirmation';

function buildSubject(ctx: ISalesFormContext): string {
    if (ctx.locale?.toLowerCase().startsWith('cs')) {
        return 'Děkujeme za váš dotaz';
    }
    return 'Thanks for reaching out';
}

export default class EcomailSendSalesCustomerConfirmationConnector extends EcomailSendTransactionalEmailConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const ctxDto = dto as unknown as ProcessDto<ISalesFormContext>;
        const ctx = ctxDto.getJsonData();
        checkParams(
            ctx as unknown as Record<string, unknown>,
            ['firstName', 'lastName', 'email'],
        );

        const appInstall = await this.getApplicationInstallFromProcess(dto);
        const settings = appInstall.getSettings()?.[SETTINGS_FORM] as Record<string, unknown> | undefined;

        const fromEmail = settings?.[FROM_EMAIL] as string | undefined;
        const fromName = settings?.[FROM_NAME] as string | undefined;
        const templateId = settings?.[SALES_CUSTOMER_TEMPLATE_ID] as number | string | undefined;

        const missing: string[] = [];
        if (!fromEmail) missing.push(FROM_EMAIL);
        if (!fromName) missing.push(FROM_NAME);
        if (templateId === undefined || templateId === null || templateId === '') missing.push(SALES_CUSTOMER_TEMPLATE_ID);

        if (missing.length > 0) {
            dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                `Missing Ecomail [${SETTINGS_FORM}] settings: ${missing.join(', ')} — configure them on the Ecomail application install.`,
            );
            return dto as unknown as ProcessDto<IOutput>;
        }

        const emailInput: IInput = {
            template_id: Number(templateId),
            subject: buildSubject(ctx),
            from_name: fromName as string,
            from_email: fromEmail as string,
            to: [{ email: ctx.email, name: `${ctx.firstName} ${ctx.lastName}` }],
            global_merge_vars: buildSalesMergeVars(ctx),
        };

        dto.setNewJsonData<IInput>(emailInput);
        await super.processAction(dto);

        const emailOutput = dto.getJsonData() as unknown as IOutput;

        return ctxDto.setNewJsonData<ISalesFormContext>({
            ...ctx,
            customerEmailId: emailOutput.id,
        }) as unknown as ProcessDto<IOutput>;
    }

}
