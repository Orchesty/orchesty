import { IOutput as IBaseInput } from '../../Common/CustomNode/HanabosoHubSpotContactMapper';
import HubspotAddContactToListMapper from '../../Common/CustomNode/HubspotAddContactToListMapper';
import { HubspotListIdsEnums } from '../../Common/Enum/HubspotListIdsEnums';

export default class HubspotApplinthContactAddContactToListMapper extends HubspotAddContactToListMapper {

    public constructor() {
        super(HubspotListIdsEnums.CONTACT_APPLINTH);
    }

    protected getListId(dto: IBaseInput): number {
        if (dto.properties.subscribed) {
            return 40;
        }
        return 47;
    }

}
