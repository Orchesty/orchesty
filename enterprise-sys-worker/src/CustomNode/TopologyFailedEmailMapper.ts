import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'topology-failed-email-mapper';

interface IInput {
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
            ok_count?: number;
            nok_count?: number;
        };
        message?: string;
    };
    recipients: string[];
}

interface IOutput {
    from: string;
    to: string;
    subject: string;
    html: string;
}

function esc(str: string): string {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

export default class TopologyFailedEmailMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
        const { event, recipients } = dto.getJsonData();

        const topologyName = event.topology?.name ?? 'Unknown topology';
        const runId = event.run?.id ?? '—';
        const okCount = event.context?.ok_count ?? 0;
        const nokCount = event.context?.nok_count ?? 0;
        const correlationId = event.context?.correlation_id ?? '—';
        const occurredAt = event.occurred_at ?? new Date().toISOString();
        const message = event.message ?? 'No details available';

        const html = `<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:40px 0">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden">
          <tr>
            <td style="background-color:#dc2626;padding:32px 40px;text-align:center">
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:600">Orchesty</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:40px">
              <h2 style="margin:0 0 16px;color:#111827;font-size:20px;font-weight:600">Topology failed</h2>
              <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6">
                ${esc(message)}
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden">
                <tr>
                  <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;width:140px">Topology</td>
                  <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;font-weight:500">${esc(topologyName)}</td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Run ID</td>
                  <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:13px;font-family:monospace">${esc(runId)}</td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">OK / NOK</td>
                  <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px"><span style="color:#16a34a">${okCount} OK</span> / <span style="color:#dc2626">${nokCount} NOK</span></td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;background-color:#f9fafb;color:#6b7280;font-size:13px">Correlation ID</td>
                  <td style="padding:12px 16px;color:#111827;font-size:13px;font-family:monospace">${esc(correlationId)}</td>
                </tr>
              </table>
              <p style="margin:0 0 8px;color:#6b7280;font-size:13px">
                Occurred at: <strong>${esc(occurredAt)}</strong>
              </p>
              <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">
              <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5">
                This is an automated notification from Orchesty. You are receiving this because you subscribed to topology failure events.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`;

        return dto.setNewJsonData<IOutput>({
            from: '"Orchesty" <noreply@orchesty.io>',
            to: recipients.join(', '),
            subject: `Topology failed \u2014 ${topologyName}`,
            html,
        });
    }

}
