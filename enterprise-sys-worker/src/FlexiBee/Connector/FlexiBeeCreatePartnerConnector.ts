import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import FlexiBeeApplication from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';

export const NAME = 'flexibee-create-partner';

/**
 * Base connector: creates a new partner in FlexiBee address book.
 *
 * Input:  { kod, nazev, ulice?, mesto?, psc?, stat?, ic, dic?, email?, tel? }
 * Output: raw FlexiBee response
 */
export default class FlexiBeeCreatePartnerConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;
        checkParams(data, ['kod', 'nazev', 'ic']);

        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const application = this.getApplication<FlexiBeeApplication>();

        const body = {
            winstrom: {
                adresar: [{
                    kod: data.kod,
                    nazev: data.nazev,
                    ulice: data.ulice ?? '',
                    mesto: data.mesto ?? '',
                    psc: data.psc ?? '',
                    stat: data.stat ?? 'code:CZ',
                    ic: data.ic,
                    dic: data.dic ?? '',
                    email: data.email ?? '',
                    tel: data.tel ?? '',
                }],
            },
        };

        const url = application.getUrl(applicationInstall, 'adresar');
        const request = await application.getRequestDto(
            dto, applicationInstall, HttpMethods.POST, url, JSON.stringify(body),
        );

        const response = await this.getSender().send(request, {
            success: [200, 201],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        dto.setData(response.getBody());
        return dto;
    }

}
