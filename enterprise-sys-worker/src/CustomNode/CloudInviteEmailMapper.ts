import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IInput } from '../Ecomail/Connector/EcomailSendMessageConnector';
import ASystemEmailMapper from './ASystemEmailMapper';

export const NAME = 'cloud-invite-email-mapper';

export interface IPayload {
    email: string;
    token: string;
    frontendUrl: string;
}

export default class CloudInviteEmailMapper extends ASystemEmailMapper {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IPayload>): Promise<ProcessDto<IInput>> {
        const sender = await this.getSystemSender(dto);
        if (!sender) {
            return dto as unknown as ProcessDto<IInput>;
        }

        const { email, token, frontendUrl } = dto.getJsonData();
        const baseUrl = frontendUrl.replace(/\/+$/, '');
        const inviteUrl = `${baseUrl}/invite?token=${encodeURIComponent(token)}`;

        const html = `<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:40px 0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
  <tr>
    <td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden">
        <tr>
          <td style="background-color:#1bea83;padding:32px 40px;text-align:center">
            <h1 style="margin:0;color:#000000;font-size:24px;font-weight:600">Orchesty Cloud</h1>
          </td>
        </tr>
        <tr>
          <td style="padding:40px">
            <h2 style="margin:0 0 16px;color:#111827;font-size:20px;font-weight:600">You've been invited</h2>
            <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6">
              You have been invited to join an organization on Orchesty Cloud. Click the button below to accept the invitation and get started.
            </p>
            <table cellpadding="0" cellspacing="0" style="margin:0 0 24px">
              <tr>
                <td style="background-color:#1bea83;border-radius:6px;padding:12px 32px">
                  <a href="${inviteUrl}" style="color:#000000;text-decoration:none;font-size:15px;font-weight:600;display:inline-block">Accept Invitation</a>
                </td>
              </tr>
            </table>
            <p style="margin:0 0 8px;color:#6b7280;font-size:13px">Or copy and paste this link into your browser:</p>
            <p style="margin:0 0 24px;color:#1bea83;font-size:13px;word-break:break-all">${inviteUrl}</p>
            <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">
            <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5">
              If you didn't expect this invitation, you can safely ignore this email.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>`;

        return dto.setNewJsonData<IInput>({
            subject: "You've been invited to Orchesty Cloud",
            from_name: sender.fromName,
            from_email: sender.fromEmail,
            to: [{ email }],
            html,
        });
    }

}
