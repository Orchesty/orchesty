import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IInput } from '../Ecomail/Connector/EcomailSendMessageConnector';
import ASystemEmailMapper from './ASystemEmailMapper';

/* eslint-disable @typescript-eslint/naming-convention */
export interface ITrialEmailPayload {
    kind: 'reminder' | 'ended';
    accountId: string;
    instanceId: string;
    email: string;
    ownerName?: string;
    locale?: string;
    instanceUrl?: string;
    configureUrl: string;
    daysRemaining?: number;
    trialEndsAt: string;
    reminderKey: string;
}
/* eslint-enable @typescript-eslint/naming-convention */

export function escapeHtml(str: string): string {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * Common base for trial-related email mappers (`trial-reminder-email-mapper`,
 * `trial-ended-email-mapper`). Concrete mappers only customise subject,
 * accent color and inner HTML body — header/footer/CTA wrapping is shared.
 *
 * Input contract: `ITrialEmailPayload` (emitted by cloud-backend
 * `cloud-trial-select-morning-events` Batch and routed via
 * `cloud-trial-event-router`).
 *
 * Output contract: `IInput` consumed by `ecomail-send-message`.
 */
export default abstract class ATrialEmailMapper extends ASystemEmailMapper {

    protected accentColor = '#1bea83';

    protected abstract buildSubject(payload: ITrialEmailPayload): string;

    protected abstract buildBodyContent(payload: ITrialEmailPayload): string;

    public async processAction(dto: ProcessDto<ITrialEmailPayload>): Promise<ProcessDto<IInput>> {
        const sender = await this.getSystemSender(dto);
        if (!sender) {
            return dto as unknown as ProcessDto<IInput>;
        }

        const payload = dto.getJsonData();
        const subject = this.buildSubject(payload);
        const body = this.buildBodyContent(payload);
        const html = this.wrapInTemplate(body, subject);

        const recipient = payload.ownerName
            ? { email: payload.email, name: payload.ownerName }
            : { email: payload.email };

        return dto.setNewJsonData<IInput>({
            subject,
            from_name: sender.fromName,
            from_email: sender.fromEmail,
            to: [recipient],
            html,
        });
    }

    /**
     * Wraps inner content into the shared Orchesty system email skeleton
     * (matches `CloudLimitThresholdEmailMapper` styling).
     */
    protected wrapInTemplate(content: string, headerTitle: string): string {
        return `<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:40px 0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden">
      <tr><td style="background-color:${this.accentColor};padding:32px 40px;text-align:center">
        <h1 style="margin:0;color:#000000;font-size:24px;font-weight:600">Orchesty Cloud</h1>
      </td></tr>
      <tr><td style="padding:40px">
        <h2 style="margin:0 0 16px;color:#111827;font-size:20px;font-weight:600">${escapeHtml(headerTitle)}</h2>
${content}
        <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">
        <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5">
          This is an automated notification from Orchesty.
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>`;
    }

}
