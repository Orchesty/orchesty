import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IInput } from '../Ecomail/Connector/EcomailSendMessageConnector';
import ASystemEmailMapper from './ASystemEmailMapper';

export const NAME = 'topology-slow-email-mapper';

/* eslint-disable @typescript-eslint/naming-convention */
export interface IPayload {
    preset_id: string;
    tenant_id: string;
    channel: string;
    event: {
        event_type: string;
        occurred_at: string;
        topology?: { id: string; name: string };
        run?: { id: string; duration_ms: number };
        severity: string;
        context?: {
            correlation_id?: string;
            duration_sec?: number;
        };
        message?: string;
    };
    recipients: string[];
}
/* eslint-enable @typescript-eslint/naming-convention */

function esc(str: string): string {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

export default class TopologySlowEmailMapper extends ASystemEmailMapper {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IPayload>): Promise<ProcessDto<IInput>> {
        const sender = await this.getSystemSender(dto);
        if (!sender) {
            return dto as unknown as ProcessDto<IInput>;
        }

        const { event, recipients } = dto.getJsonData();
        const topologyName = event.topology?.name ?? 'Unknown topology';
        const runId = event.run?.id ?? '—';
        const durationSec = event.context?.duration_sec ?? 0;
        const durationFormatted = durationSec >= 60
            ? `${Math.floor(durationSec / 60)}m ${durationSec % 60}s`
            : `${durationSec}s`;
        const correlationId = event.context?.correlation_id ?? '—';
        const occurredAt = event.occurred_at ?? new Date().toISOString();

        const html = `<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:40px 0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
  <tr>
    <td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden">
        <tr>
          <td style="background-color:#d97706;padding:32px 40px;text-align:center">
            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:600">Orchesty</h1>
          </td>
        </tr>
        <tr>
          <td style="padding:40px">
            <h2 style="margin:0 0 16px;color:#111827;font-size:20px;font-weight:600">Slow topology detected</h2>
            <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6">
              Topology <strong>${esc(topologyName)}</strong> took longer than expected to complete.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden">
              <tr>
                <td style="padding:12px 16px;background-color:#fffbeb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;width:140px">Topology</td>
                <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;font-weight:500">${esc(topologyName)}</td>
              </tr>
              <tr>
                <td style="padding:12px 16px;background-color:#fffbeb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Run ID</td>
                <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:13px;font-family:monospace">${esc(runId)}</td>
              </tr>
              <tr>
                <td style="padding:12px 16px;background-color:#fffbeb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Duration</td>
                <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#d97706;font-size:14px;font-weight:600">${esc(durationFormatted)}</td>
              </tr>
              <tr>
                <td style="padding:12px 16px;background-color:#fffbeb;color:#6b7280;font-size:13px">Correlation ID</td>
                <td style="padding:12px 16px;color:#111827;font-size:13px;font-family:monospace">${esc(correlationId)}</td>
              </tr>
            </table>
            <p style="margin:0 0 8px;color:#6b7280;font-size:13px">
              Occurred at: <strong>${esc(occurredAt)}</strong>
            </p>
            <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">
            <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5">
              This is an automated notification from Orchesty. You are receiving this because you subscribed to slow topology events.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>`;

        /* eslint-disable @typescript-eslint/naming-convention */
        return dto.setNewJsonData<IInput>({
            subject: `Slow topology \u2014 ${topologyName}`,
            from_name: sender.fromName,
            from_email: sender.fromEmail,
            to: recipients.map((email) => ({ email })),
            html,
        });
        /* eslint-enable @typescript-eslint/naming-convention */
    }

}
