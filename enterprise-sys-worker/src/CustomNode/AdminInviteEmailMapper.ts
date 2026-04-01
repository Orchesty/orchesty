import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'admin-invite-email-mapper';

interface IInput {
    email: string;
    name: string;
    role: string;
    resetUrl: string;
    frontendUrl: string;
}

interface IOutput {
    from: string;
    to: string;
    subject: string;
    html: string;
}

export default class AdminInviteEmailMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
        const { email, name, role, resetUrl } = dto.getJsonData();

        const roleLabel = role === 'SUPER_ADMIN' ? 'Super Admin' : 'Support';

        const html = `<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:40px 0">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden">
          <tr>
            <td style="background-color:#1a56db;padding:32px 40px;text-align:center">
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:600">Orchesty Admin</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:40px">
              <h2 style="margin:0 0 16px;color:#111827;font-size:20px;font-weight:600">You've been invited</h2>
              <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6">
                Hello ${name},<br><br>
                You have been invited to join the Orchesty Admin Panel as <strong>${roleLabel}</strong>.                 Click the button below to create your account and get started.
              </p>
              <table cellpadding="0" cellspacing="0" style="margin:0 0 24px">
                <tr>
                  <td style="background-color:#1a56db;border-radius:6px;padding:12px 32px">
                    <a href="${resetUrl}" style="color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;display:inline-block">Sign Up & Activate</a>
                  </td>
                </tr>
              </table>
              <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">
              <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5">
                If you didn't expect this invitation, you can safely ignore this email.
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
            from: '"Orchesty Admin" <noreply@orchesty.io>',
            to: email,
            subject: `You've been invited to Orchesty Admin as ${roleLabel}`,
            html,
        });
    }

}
