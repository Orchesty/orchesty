import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { IOutput as IInput } from '@orchesty/connector-wflow/dist/Connector/WflowGetDocumentConnector';
import { NAME as WFLOW_NAME } from '@orchesty/connector-wflow/dist/WflowApplication';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { FLEXI_BEE_FORM } from '../../Wflow/WflowApplication';
import { CUSTOM_COMPANY_ID, FlexiBeeApplication } from '../FlexiBeeApplication';
import { FirmaPayload, Payload } from '../types/payload';

export const FIRMA_KOD = 'firmaKod';

export const NAME = `${FLEXI_BEE_APPLICATION}-find-firma-kod-connector`;

export default class FlexiBeeFirmaKodFindIdConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const app = this.getApplication<FlexiBeeApplication>();
        const appInstall = await this.getApplicationInstallFromProcess(dto);
        const { id, partnerIC: ic, type: { id: typeId, name: typeName } } = dto.getJsonData();

        const applicationInstall = await this
            .getDbClient()
            .getApplicationRepository()
            .findByNameAndUser(WFLOW_NAME, dto.getUser() as string);
        const companyId = applicationInstall?.getSettings()?.[FLEXI_BEE_FORM]?.[typeId] as string | undefined;

        if (!companyId) {
            return dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                `Invoice ${id} could not be sent because it has type ${typeId} (${typeName}) that was not mapped.`,
            );
        }

        dto.addHeader(CUSTOM_COMPANY_ID, companyId);

        const request = await app.getRequestDto(
            dto,
            appInstall,
            HttpMethods.GET,
            app.getUrl(appInstall, `adresar/(ic='${ic}')`),
        );

        const {
            winstrom: {
                adresar,
            },
        } = (
            await this.getSender().send<Payload<FirmaPayload>>(request)
        ).getJsonBody();

        return adresar.length ? dto.addHeader(FIRMA_KOD, adresar[0].kod) : dto;
    }

}
