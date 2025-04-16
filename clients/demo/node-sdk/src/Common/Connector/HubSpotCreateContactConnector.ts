import Base from '@orchesty/nodejs-connectors/dist/lib/Hubspot/Connector/HubSpotCreateContactConnector';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IOutput as IInput } from '../CustomNode/HanabosoHubSpotContactMapper';

export default class HubSpotCreateContactConnector extends Base {

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const data = dto.getJsonData();
        const { language } = data.properties;
        delete data.properties.language;
        delete data.properties.subscribed;

        // eslint-disable-next-line
        const superDto = await super.processAction(dto.setNewJsonData<any>(data));
        const out = {
            ...(superDto.getJsonData() as object),
            ...{ email: data.properties.email, language },
        };

        return superDto.setNewJsonData<unknown>({ properties: out });
    }

}
