import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import FlexiBeeApplication from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';

export const NAME = 'flexibee-create-faktura-vydana-connector';

export default class FlexiBeeCreateFakturaVydanaConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData();

        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const application = this.getApplication<FlexiBeeApplication>();

        const body = {
            winstrom: {
                'faktura-vydana': Array.isArray(data) ? data : [data],
            },
        };

        const url = application.getUrl(applicationInstall, 'faktura-vydana');
        const request = await application.getRequestDto(
            dto,
            applicationInstall,
            HttpMethods.POST,
            url,
            JSON.stringify(body),
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
