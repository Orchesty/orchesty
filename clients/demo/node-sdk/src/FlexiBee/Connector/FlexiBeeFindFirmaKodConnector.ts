import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { IOutput as IInput } from '@orchesty/connector-wflow/dist/Connector/WflowGetDocumentConnector';
import { NAME as WFLOW_NAME } from '@orchesty/connector-wflow/dist/WflowApplication';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import OnStopAndFailException from '@orchesty/nodejs-sdk/dist/lib/Exception/OnStopAndFailException';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import WflowDocumentToFlexibeeFakturaPrijataMapper from '../../Wflow/CustomNode/WflowDocumentToFlexibeeFakturaPrijataMapper';
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
        const {
            id,
            partnerIC: ic,
            partnerVAT: vat,
            partnerName: name,
            partnerAddress,
            type: { id: typeId, name: typeName },
        } = dto.getJsonData();

        const applicationInstall = await this
            .getDbClient()
            .getApplicationRepository()
            .findByNameAndUser(WFLOW_NAME, dto.getUser() as string, [dto.getSdk() ?? '']);
        const companyId = applicationInstall?.getSettings()?.[FLEXI_BEE_FORM]?.[typeId] as string | undefined;

        if (!companyId) {
            return dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                `Invoice ${id} could not be sent because it has type ${typeId} (${typeName}) that was not mapped.`,
            );
        }

        dto.addHeader(CUSTOM_COMPANY_ID, companyId);

        const firmaKod = ic ?? vat ?? FlexiBeeApplication.createCode(name);

        const requestDto = await app.getRequestDto(
            dto,
            appInstall,
            HttpMethods.GET,
            app.getUrl(appInstall, `adresar/(kod='${firmaKod}')`),
        );

        const { winstrom: { adresar } } = (
            await this.getSender().send<Payload<FirmaPayload>>(requestDto)
        ).getJsonBody();

        if (adresar.length) {
            return dto.addHeader(FIRMA_KOD, adresar[0].kod);
        }

        const { ulice, mesto, psc } = WflowDocumentToFlexibeeFakturaPrijataMapper.parseAddress(partnerAddress);
        const createRequestDto = await app.getRequestDto(
            dto,
            appInstall,
            HttpMethods.PUT,
            app.getUrl(appInstall, 'adresar'),
            /* eslint-disable @typescript-eslint/naming-convention */
            {
                winstrom: {
                    '@version': '1.0',
                    adresar: [{
                        kod: firmaKod,
                        nazev: name,
                        ulice,
                        mesto,
                        psc,
                        ic,
                        dic: vat,
                    }],
                },
            },
            /* eslint-enable @typescript-eslint/naming-convention */
        );

        try {
            await this.getSender().send(createRequestDto);

            return dto.addHeader(FIRMA_KOD, firmaKod);
        } catch (e) {
            if (!(e instanceof OnStopAndFailException)) {
                throw e;
            }

            const retryRequestDto = await app.getRequestDto(
                dto,
                appInstall,
                HttpMethods.GET,
                app.getUrl(appInstall, `adresar/(kod='${firmaKod}')`),
            );

            const { winstrom: { adresar: retryAdresar } } = (
                await this.getSender().send<Payload<FirmaPayload>>(retryRequestDto)
            ).getJsonBody();

            if (retryAdresar.length) {
                return dto.addHeader(FIRMA_KOD, retryAdresar[0].kod);
            }

            return dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                `Invoice ${id} could not be sent because company ${firmaKod} does not exist and could not be created.`,
            );
        }
    }

}
