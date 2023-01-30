import Base from '@orchesty/nodejs-connectors/dist/lib/Hubspot/Connector/HubSpotCreateContactConnector';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IOutput as IInput } from '../CustomNode/HanabosoHubSpotContactMapper';

export default class HubSpotCreateContactConnector extends Base {

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { properties } = dto.getJsonData();
        const superDto = await super.processAction(dto);

        const out = {
            ...(superDto.getJsonData() as object),
            ...{ email: properties.email },
        };

        return superDto.setNewJsonData<unknown>({ properties: out });
    }

}
