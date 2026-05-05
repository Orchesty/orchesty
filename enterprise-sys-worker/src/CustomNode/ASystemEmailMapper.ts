import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { SYSTEM_FORM, SYSTEM_FROM_EMAIL, SYSTEM_FROM_NAME } from '../Ecomail/EcomailApplication';

export interface ISystemSender {
    fromEmail: string;
    fromName: string;
}

export default abstract class ASystemEmailMapper extends ACommonNode {

    /**
     * Loads the Ecomail [system] sender (from_email + from_name) from the
     * application install. If either field is missing, the dto is marked
     * STOP_AND_FAILED and `null` is returned — callers should bail out and
     * return the dto unchanged.
     */
    protected async getSystemSender(dto: ProcessDto): Promise<ISystemSender | null> {
        const appInstall = await this.getApplicationInstallFromProcess(dto);
        const settings = appInstall.getSettings()?.[SYSTEM_FORM] as Record<string, unknown> | undefined;
        const fromEmail = settings?.[SYSTEM_FROM_EMAIL] as string | undefined;
        const fromName = settings?.[SYSTEM_FROM_NAME] as string | undefined;

        const missing: string[] = [];
        if (!fromEmail) {
            missing.push(SYSTEM_FROM_EMAIL);
        }
        if (!fromName) {
            missing.push(SYSTEM_FROM_NAME);
        }
        if (missing.length > 0) {
            dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                `Missing Ecomail [${SYSTEM_FORM}] settings: ${missing.join(', ')} — configure them on the Ecomail application install.`,
            );

            return null;
        }

        return { fromEmail: fromEmail as string, fromName: fromName as string };
    }

}
