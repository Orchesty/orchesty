import ATrialEmailMapper, { escapeHtml, ITrialEmailPayload } from './ATrialEmailMapper';

export const NAME = 'trial-ended-email-mapper';

export default class TrialEndedEmailMapper extends ATrialEmailMapper {

    public getName(): string {
        return NAME;
    }

    protected buildSubject(): string {
        return 'Your Orchesty trial has ended — instance now running on Starter';
    }

    protected buildBodyContent(payload: ITrialEmailPayload): string {
        const greeting = payload.ownerName ? `Hi ${escapeHtml(payload.ownerName)},` : 'Hi,';
        const configureUrl = escapeHtml(payload.configureUrl);

        return `        <p style="margin:0 0 16px;color:#4b5563;font-size:15px;line-height:1.6">${greeting}</p>
        <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6">
          Your Orchesty trial has ended. To keep things running, your instance has been automatically downgraded to the <strong>Starter</strong> plan. Enterprise features (advanced monitoring, audit logs, AI assistance) are no longer enabled.
        </p>
        <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6">
          You can upgrade at any time and configure resources to match your workload.
        </p>
        <table align="center" cellpadding="0" cellspacing="0" style="margin:0 auto 24px">
          <tr>
            <td style="background-color:${this.accentColor};border-radius:6px;padding:12px 32px">
              <a href="${configureUrl}" style="color:#000000;text-decoration:none;font-size:15px;font-weight:600;display:inline-block">Choose your plan</a>
            </td>
          </tr>
        </table>
        <p style="margin:0 0 8px;color:#6b7280;font-size:13px">Thank you for trying Orchesty — we'd love to hear how it went. Just reply to this email if you have any questions.</p>`;
    }

}
