import { IInput as IOuput } from '@orchesty/nodejs-connectors/dist/lib/Hubspot/Connector/HubSpotAddEmailToListConnector';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { getHubspotListName, HubspotListIdsEnums } from '../Enum/HubspotListIdsEnums';
import { IOutput as IInput } from './OrchestyToHubSpotContactMapper';

export default class HubspotAddContactToListMapper extends ACommonNode {

    public constructor(private readonly hubSpotList: HubspotListIdsEnums) {
        super();
    }

    public getName(): string {
        return `hubspot-add-contact-to-list-${getHubspotListName(this.hubSpotList)}-mapper`;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IInput | IOuput> {
        const { properties } = dto.getJsonData();

        if (!properties?.email) {
            dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Email is not defined');
            return dto;
        }

        return dto.setNewJsonData({
            emails: [properties.email],
            listId: this.hubSpotList,
        });
    }

}
