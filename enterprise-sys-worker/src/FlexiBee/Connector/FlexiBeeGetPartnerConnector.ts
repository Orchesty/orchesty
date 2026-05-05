import FlexiBeeApplication from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';

export const NAME = 'flexibee-get-partner';

/**
 * Base connector: looks up a partner in FlexiBee address book by IČO.
 *
 * Input:  { ic: string }
 * Output: raw FlexiBee response (partner record or empty result)
 */
export default class FlexiBeeGetPartnerConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;
        checkParams(data, ['ic']);

        const ic = data.ic as string;
        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const application = this.getApplication<FlexiBeeApplication>();

        const url = application.getUrl(applicationInstall, `adresar/(ic='${ic}').json`);
        const request = await application.getRequestDto(dto, applicationInstall, HttpMethods.GET, url);

        const response = await this.getSender().send(request, {
            success: [200],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        dto.setData(response.getBody());
        return dto;
    }

}
