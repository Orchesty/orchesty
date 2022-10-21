import Base, { IInput } from '@orchesty/nodejs-connectors/dist/lib/Hubspot/Connector/HubSpotAddEmailToListConnector';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export default class HubSpotAddEmailToListConnector extends Base {

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const { emails } = dto.getJsonData();
        const superDto = await super.processAction(dto);
        return superDto.setNewJsonData<unknown>({ ...(superDto.getJsonData() as object), emails });
    }

}
