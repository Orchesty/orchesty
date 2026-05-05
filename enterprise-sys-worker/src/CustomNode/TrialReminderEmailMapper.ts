import ATrialEmailMapper, { escapeHtml, ITrialEmailPayload } from './ATrialEmailMapper';

export const NAME = 'trial-reminder-email-mapper';

export default class TrialReminderEmailMapper extends ATrialEmailMapper {

    public getName(): string {
        return NAME;
    }

    protected buildSubject(payload: ITrialEmailPayload): string {
        const days = payload.daysRemaining ?? 0;
        if (days <= 1) {
            return 'Your Orchesty trial ends tomorrow — finalise your plan';
        }
        return `Your Orchesty trial ends in ${days} days — finalise your plan`;
    }

    protected buildBodyContent(payload: ITrialEmailPayload): string {
        const days = payload.daysRemaining ?? 0;
        const urgent = days <= 1;
        const daysHighlight = urgent ? '#dc2626' : '#111827';

        const greeting = payload.ownerName ? `Hi ${escapeHtml(payload.ownerName)},` : 'Hi,';
        const trialEndsAt = escapeHtml(payload.trialEndsAt);
        const configureUrl = escapeHtml(payload.configureUrl);
        const daysLabel = days === 1 ? '1 day' : `${days} days`;

        return `        <p style="margin:0 0 16px;color:#4b5563;font-size:15px;line-height:1.6">${greeting}</p>
        <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6">
          Your Orchesty trial ends in <strong>${escapeHtml(daysLabel)}</strong>. To keep your instance running with the resources and features you need, please finalise your subscription before the trial expires.
        </p>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden">
          <tr>
            <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;width:160px">Days remaining</td>
            <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:${daysHighlight};font-size:14px;font-weight:600">${escapeHtml(daysLabel)}</td>
          </tr>
          <tr>
            <td style="padding:12px 16px;background-color:#f9fafb;color:#6b7280;font-size:13px">Trial ends at</td>
            <td style="padding:12px 16px;color:#111827;font-size:14px">${trialEndsAt}</td>
          </tr>
        </table>
        <table align="center" cellpadding="0" cellspacing="0" style="margin:0 auto 24px">
          <tr>
            <td style="background-color:${this.accentColor};border-radius:6px;padding:12px 32px">
              <a href="${configureUrl}" style="color:#000000;text-decoration:none;font-size:15px;font-weight:600;display:inline-block">Configure your plan</a>
            </td>
          </tr>
        </table>
        <p style="margin:0 0 8px;color:#6b7280;font-size:13px">If you do not finalise your subscription, your instance will be automatically downgraded to the Starter plan after the trial ends.</p>`;
    }

}
