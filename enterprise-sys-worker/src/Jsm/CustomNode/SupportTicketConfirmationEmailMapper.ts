import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { IInput as IEcomailMessage } from '../../Ecomail/Connector/EcomailSendMessageConnector';
import { SUPPORT_FORM, SUPPORT_FROM_EMAIL, SUPPORT_FROM_NAME } from '../../Ecomail/EcomailApplication';
import { IOutput as ITicketContext } from '../Connector/JsmCreateCustomerRequest';

export const NAME = 'support-ticket-confirmation-email-mapper';

function esc(str: string): string {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

interface ISupportSender {
    fromEmail: string;
    fromName: string;
}

function detailRow(label: string, value: string, opts: { strong?: boolean } = {}): string {
    const valueWeight = opts.strong ? 'font-weight:600' : 'font-weight:500';
    return `<tr>
                <td style="padding:12px 16px;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;width:140px">${label}</td>
                <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;${valueWeight}">${value}</td>
              </tr>`;
}

interface IRenderArgs {
    recipientName: string;
    issueKey: string;
    summary: string;
    categoryLabel: string;
    accountName: string;
    portalUrl: string;
}

function renderHtml(args: IRenderArgs): string {
    const greeting = args.recipientName
        ? `Hello ${esc(args.recipientName)},`
        : 'Hello,';

    const detailRows: string[] = [];
    if (args.issueKey) {
        detailRows.push(detailRow('Ticket', esc(args.issueKey), { strong: true }));
    }
    if (args.summary) {
        detailRows.push(detailRow('Summary', esc(args.summary)));
    }
    if (args.categoryLabel) {
        detailRows.push(detailRow('Category', esc(args.categoryLabel)));
    }
    if (args.accountName) {
        detailRows.push(detailRow('Account', esc(args.accountName)));
    }

    const portalCta = args.portalUrl
        ? `<p style="margin:24px 0 0;color:#4b5563;font-size:14px;line-height:1.6">
            You can track progress, reply, and add attachments in the Orchesty support portal:
            <br>
            <a href="${esc(args.portalUrl)}" style="display:inline-block;margin-top:12px;padding:10px 20px;background-color:#4f46e5;color:#ffffff;text-decoration:none;border-radius:6px;font-size:14px;font-weight:500">View ticket</a>
          </p>`
        : '';

    return `<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:40px 0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
  <tr>
    <td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden">
        <tr>
          <td style="background-color:#4f46e5;padding:32px 40px;text-align:center">
            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:600">Orchesty Support</h1>
          </td>
        </tr>
        <tr>
          <td style="padding:40px">
            <h2 style="margin:0 0 16px;color:#111827;font-size:20px;font-weight:600">We've received your request</h2>
            <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6">
              ${greeting}
              <br><br>
              Thank you for reaching out. Your support request has been logged and our team will get back to you as soon as possible. The details below summarise what we received.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden">
              ${detailRows.join('\n              ')}
            </table>
            ${portalCta}
            <hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0 24px">
            <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5">
              This is an automated confirmation from Orchesty. You can reply to this email or use the portal link above to add details to your ticket.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>`;
}

/**
 * Builds the customer-facing confirmation email after a JSM ticket is created.
 *
 * Mirrors the system-notification mappers (`LimitOverflowEmailMapper`, etc.):
 * the HTML body is composed in code instead of relying on an Ecomail template,
 * which means the message ships with the worker (no out-of-band template
 * editing) and the Ecomail install only needs the `support` sender pair —
 * `support_from_email` and `support_from_name`. Output matches the universal
 * `EcomailSendMessageConnector` input contract so the same connector that
 * handles all transactional notifications also handles support confirmations.
 */
export default class SupportTicketConfirmationEmailMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<ITicketContext>): Promise<ProcessDto<IEcomailMessage>> {
        const sender = await this.getSupportSender(dto);
        if (!sender) {
            return dto as unknown as ProcessDto<IEcomailMessage>;
        }

        const ctx = dto.getJsonData();
        const recipient = ctx.userEmail;
        if (!recipient) {
            dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                'Missing userEmail on JSM create-customer-request output — cannot send confirmation.',
            );
            return dto as unknown as ProcessDto<IEcomailMessage>;
        }

        const recipientName = ctx.userName ?? '';
        const issueKey = ctx.issueKey ?? '';
        const summary = ctx.summary ?? '';
        const categoryLabel = ctx.categoryLabel ?? '';
        const accountName = ctx.accountName ?? '';
        const portalUrl = ctx.portalUrl ?? '';

        const subject = issueKey
            ? `[${issueKey}] ${summary}`.trim()
            : (summary || 'Support ticket received');

        const html = renderHtml({ recipientName, issueKey, summary, categoryLabel, accountName, portalUrl });

        /* eslint-disable @typescript-eslint/naming-convention */
        return dto.setNewJsonData<IEcomailMessage>({
            subject,
            from_name: sender.fromName,
            from_email: sender.fromEmail,
            to: recipientName
                ? [{ email: recipient, name: recipientName }]
                : [{ email: recipient }],
            html,
        });
        /* eslint-enable @typescript-eslint/naming-convention */
    }

    /**
     * Loads the Ecomail [support] sender pair from the application install.
     * Mirrors `ASystemEmailMapper.getSystemSender` but reads the `support`
     * form so the support reply-to is independent of system / sales notifs.
     * Returns `null` after marking the dto STOP_AND_FAILED if either field
     * is missing — the caller bails out without dispatching.
     */
    private async getSupportSender(dto: ProcessDto): Promise<ISupportSender | null> {
        const appInstall = await this.getApplicationInstallFromProcess(dto);
        const settings = appInstall.getSettings()?.[SUPPORT_FORM] as Record<string, unknown> | undefined;
        const fromEmail = settings?.[SUPPORT_FROM_EMAIL] as string | undefined;
        const fromName = settings?.[SUPPORT_FROM_NAME] as string | undefined;

        const missing: string[] = [];
        if (!fromEmail) {
            missing.push(SUPPORT_FROM_EMAIL);
        }
        if (!fromName) {
            missing.push(SUPPORT_FROM_NAME);
        }

        if (missing.length > 0) {
            dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                `Missing Ecomail [${SUPPORT_FORM}] settings: ${missing.join(', ')} — configure them on the Ecomail application install.`,
            );
            return null;
        }

        return { fromEmail: fromEmail as string, fromName: fromName as string };
    }

}
