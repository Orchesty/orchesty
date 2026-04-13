import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'topology-failed-message-email-mapper';

/* eslint-disable @typescript-eslint/naming-convention */
interface IBufferedEvent {
    node_name: string;
    error_message: string;
}

interface IInput {
    preset_id: string;
    tenant_id: string;
    channel: string;
    event: {
        event_type: string;
        occurred_at: string;
        topology?: { id: string; name: string };
        node?: { id: string; name: string };
        severity: string;
        context?: {
            trash_id?: string;
            correlation_id?: string;
            process_id?: string;
            result_message?: string;
        };
        message?: string;
    };
    recipients: string[];
    events?: IBufferedEvent[];
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

function buildSingleEventHtml(
    topologyName: string,
    nodeName: string,
    errorMessage: string,
    occurredAt: string,
    correlationId: string,
    trashId: string,
): string {
    return `<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:40px 0">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden">
          <tr>
            <td style="background-color:#1a56db;padding:32px 40px;text-align:center">
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:600">Orchesty</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:40px">
              <h2 style="margin:0 0 16px;color:#111827;font-size:20px;font-weight:600">Failed message in topology</h2>
              <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6">
                A message failed during processing and was moved to trash. Details below:
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden">
                <tr>
                  <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;width:140px">Topology</td>
                  <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;font-weight:500">${esc(topologyName)}</td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Node</td>
                  <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px">${esc(nodeName)}</td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Error</td>
                  <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#dc2626;font-size:14px">${esc(errorMessage)}</td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Correlation ID</td>
                  <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:13px;font-family:monospace">${esc(correlationId)}</td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;background-color:#f9fafb;color:#6b7280;font-size:13px">Trash ID</td>
                  <td style="padding:12px 16px;color:#111827;font-size:13px;font-family:monospace">${esc(trashId)}</td>
                </tr>
              </table>
              <p style="margin:0 0 8px;color:#6b7280;font-size:13px">
                Occurred at: <strong>${esc(occurredAt)}</strong>
              </p>
              <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">
              <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5">
                This is an automated notification from Orchesty. You are receiving this because you subscribed to failed message events.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`;
}

function buildBufferedEventsHtml(topologyName: string, occurredAt: string, events: IBufferedEvent[]): string {
    const rows = events.map((ev) => `
                <tr>
                  <td style="padding:10px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px">${esc(ev.node_name || 'Unknown node')}</td>
                  <td style="padding:10px 16px;border-bottom:1px solid #e5e7eb;color:#dc2626;font-size:13px">${esc(ev.error_message || 'No details')}</td>
                </tr>`).join('');

    return `<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:40px 0">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden">
          <tr>
            <td style="background-color:#1a56db;padding:32px 40px;text-align:center">
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:600">Orchesty</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:40px">
              <h2 style="margin:0 0 16px;color:#111827;font-size:20px;font-weight:600">Failed messages in topology</h2>
              <p style="margin:0 0 8px;color:#4b5563;font-size:15px;line-height:1.6">
                <strong>${esc(topologyName)}</strong> \u2014 ${events.length} unique error(s) detected and moved to trash.
              </p>
              <p style="margin:0 0 24px;color:#6b7280;font-size:13px">
                First occurrence: <strong>${esc(occurredAt)}</strong>
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden">
                <tr>
                  <td style="padding:10px 16px;background-color:#f3f4f6;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:12px;font-weight:600;text-transform:uppercase">Node</td>
                  <td style="padding:10px 16px;background-color:#f3f4f6;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:12px;font-weight:600;text-transform:uppercase">Error</td>
                </tr>${rows}
              </table>
              <p style="margin:0 0 8px;color:#6b7280;font-size:13px">
                Review all failed messages in the <strong>Failed Messages</strong> section of Orchesty.
              </p>
              <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">
              <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5">
                This is an automated notification from Orchesty. You are receiving this because you subscribed to failed message events.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`;
}

export default class TopologyFailedMessageEmailMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
        const { event, events, recipients } = dto.getJsonData();

        const topologyName = event.topology?.name ?? 'Unknown topology';
        const occurredAt = event.occurred_at ?? new Date().toISOString();

        let html: string;
        let subject: string;

        if (events && events.length > 0) {
            html = buildBufferedEventsHtml(topologyName, occurredAt, events);
            subject = events.length === 1
                ? `Failed message \u2014 ${topologyName}`
                : `Failed messages (${events.length}) \u2014 ${topologyName}`;
        } else {
            const nodeName = event.node?.name ?? 'Unknown node';
            const errorMessage = event.context?.result_message ?? event.message ?? 'No details available';
            const trashId = event.context?.trash_id ?? '\u2014';
            const correlationId = event.context?.correlation_id ?? '\u2014';

            html = buildSingleEventHtml(topologyName, nodeName, errorMessage, occurredAt, correlationId, trashId);
            subject = `Failed message \u2014 ${topologyName}`;
        }

        return dto.setNewJsonData<IOutput>({
            from: '"Orchesty" <noreply@orchesty.io>',
            to: recipients.join(', '),
            subject,
            html,
        });
    }

}
