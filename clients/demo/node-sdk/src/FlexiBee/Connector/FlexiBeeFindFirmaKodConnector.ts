import FlexiBeeApplication, { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { IOutput as IInput } from '@orchesty/connector-wflow/dist/Connector/WflowGetDocumentConnector';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
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
        const { partnerIC: ic } = dto.getJsonData();

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
