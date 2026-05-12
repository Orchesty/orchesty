import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { IOutput as ITicketContext } from '../../Jsm/Connector/JsmCreateCustomerRequest';
import {
    SUPPORT_CONFIRMATION_TEMPLATE_ID,
    SUPPORT_FORM,
    SUPPORT_FROM_EMAIL,
    SUPPORT_FROM_NAME,
} from '../EcomailApplication';
import EcomailSendTransactionalEmailConnector, {
    IInput,
    IOutput,
} from './EcomailSendTransactionalEmailConnector';

export const NAME = 'ecomail-send-support-ticket-confirmation';

export default class EcomailSendSupportTicketConfirmationConnector extends EcomailSendTransactionalEmailConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const ctxDto = dto as unknown as ProcessDto<ITicketContext>;
        const ctx = ctxDto.getJsonData();
        checkParams(
            ctx as unknown as Record<string, unknown>,
            ['issueKey', 'summary', 'userEmail'],
        );

        const appInstall = await this.getApplicationInstallFromProcess(dto);
        const settings = appInstall.getSettings()?.[SUPPORT_FORM] as Record<string, unknown> | undefined;

        const fromEmail = settings?.[SUPPORT_FROM_EMAIL] as string | undefined;
        const fromName = settings?.[SUPPORT_FROM_NAME] as string | undefined;
        const templateId = settings?.[SUPPORT_CONFIRMATION_TEMPLATE_ID] as number | string | undefined;

        const missing: string[] = [];
        if (!fromEmail) missing.push(SUPPORT_FROM_EMAIL);
        if (!fromName) missing.push(SUPPORT_FROM_NAME);
        if (templateId === undefined || templateId === null || templateId === '') {
            missing.push(SUPPORT_CONFIRMATION_TEMPLATE_ID);
        }

        if (missing.length > 0) {
            dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                `Missing Ecomail [${SUPPORT_FORM}] settings: ${missing.join(', ')} — configure them on the Ecomail application install.`,
            );
            return dto as unknown as ProcessDto<IOutput>;
        }

        const recipientName = ctx.userName || '';

        /* eslint-disable @typescript-eslint/naming-convention */
        const emailInput: IInput = {
            template_id: Number(templateId),
            subject: `[${ctx.issueKey}] ${ctx.summary}`,
            from_name: fromName as string,
            from_email: fromEmail as string,
            to: recipientName
                ? [{ email: ctx.userEmail, name: recipientName }]
                : [{ email: ctx.userEmail }],
            global_merge_vars: [
                { name: 'JSM_ISSUE_KEY', content: ctx.issueKey },
                { name: 'SUMMARY', content: ctx.summary },
                { name: 'CATEGORY_LABEL', content: ctx.categoryLabel ?? '' },
                { name: 'ACCOUNT_NAME', content: ctx.accountName ?? '' },
                { name: 'USER_NAME', content: recipientName },
                { name: 'PORTAL_URL', content: ctx.portalUrl ?? '' },
            ],
        };
        /* eslint-enable @typescript-eslint/naming-convention */

        dto.setNewJsonData<IInput>(emailInput);
        await super.processAction(dto);

        // The transactional Ecomail response (id / acceptance counts) is the
        // last node's output. We don't need to thread the JSM context further;
        // the topology ends here.
        return dto as unknown as ProcessDto<IOutput>;
    }

}
