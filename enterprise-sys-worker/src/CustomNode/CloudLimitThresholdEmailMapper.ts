import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'cloud-limit-threshold-email-mapper';

/* eslint-disable @typescript-eslint/naming-convention */
interface IInput {
    preset_id: string;
    tenant_id: string;
    channel: string;
    event: {
        event_type: string;
        occurred_at: string;
        severity: string;
        context?: {
            resource?: string;
            band?: string;
            percent?: number | null;
            current?: number;
            limit?: number;
        };
        message?: string;
    };
    recipients: string[];
}
/* eslint-enable @typescript-eslint/naming-convention */

interface IOutput {
    from: string;
    to: string;
    subject: string;
    html: string;
}

function esc(str: string): string {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function resourceLabel(resource: string): string {
    switch (resource) {
        case 'messages':
            return 'Messages in flight';
        case 'storage':
            return 'Storage';
        default:
            return resource || 'Unknown resource';
    }
}

function bandLabel(band: string): string {
    switch (band) {
        case 'warning':
            return '80% reached';
        case 'critical':
            return '90% reached';
        case 'exceeded':
            return 'Limit exceeded';
        default:
            return band || 'Threshold reached';
    }
}

export default class CloudLimitThresholdEmailMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
        const { event, recipients } = dto.getJsonData();

        const ctx = event.context ?? {};
        const resource = ctx.resource ?? 'unknown';
        const band = ctx.band ?? 'warning';
        const percent = ctx.percent ?? 0;
        const current = ctx.current ?? 0;
        const limit = ctx.limit ?? 0;
        const occurredAt = event.occurred_at ?? new Date().toISOString();
        const message = event.message ?? `Cloud plan ${resource} threshold reached`;
        const accentColor = band === 'exceeded' || band === 'critical' ? '#dc2626' : '#f59e0b';

        const html = `<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:40px 0">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden">
        <tr><td style="background-color:${accentColor};padding:32px 40px;text-align:center">
          <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:600">Orchesty</h1>
        </td></tr>
        <tr><td style="padding:40px">
          <h2 style="margin:0 0 16px;color:#111827;font-size:20px;font-weight:600">Cloud plan limit ${esc(bandLabel(band))}</h2>
          <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6">${esc(message)}</p>
          <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden">
            <tr>
              <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;width:160px">Resource</td>
              <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;font-weight:500">${esc(resourceLabel(resource))}</td>
            </tr>
            <tr>
              <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Utilization</td>
              <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:${accentColor};font-size:14px;font-weight:600">${percent ? `${Number(percent).toFixed(1) /* eslint-disable-line @typescript-eslint/no-unnecessary-type-conversion */}%` : '—'}</td>
            </tr>
            <tr>
              <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Current</td>
              <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px">${Number(current).toLocaleString() /* eslint-disable-line @typescript-eslint/no-unnecessary-type-conversion */}</td>
            </tr>
            <tr>
              <td style="padding:12px 16px;background-color:#f9fafb;color:#6b7280;font-size:13px">Limit</td>
              <td style="padding:12px 16px;color:#111827;font-size:14px">${Number(limit).toLocaleString() /* eslint-disable-line @typescript-eslint/no-unnecessary-type-conversion */}</td>
            </tr>
          </table>
          <p style="margin:0 0 8px;color:#6b7280;font-size:13px">Occurred at: <strong>${esc(occurredAt)}</strong></p>
          <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">
          <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5">
            This is an automated notification from Orchesty. Approaching or exceeding cloud plan limits may lead to dropped messages.
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>`;

        return dto.setNewJsonData<IOutput>({
            from: '"Orchesty" <noreply@orchesty.io>',
            to: recipients.join(', '),
            subject: `Cloud plan ${esc(resourceLabel(resource))} \u2014 ${esc(bandLabel(band))}`,
            html,
        });
    }

}
