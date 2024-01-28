import { IOutput as IBaseInput } from '../../Common/CustomNode/HanabosoHubSpotContactMapper';
import HubspotAddContactToListMapper from '../../Common/CustomNode/HubspotAddContactToListMapper';
import { HubspotListIdsEnums } from '../../Common/Enum/HubspotListIdsEnums';

export default class HubspotApplinthWhitePaperAddContactToListMapper extends HubspotAddContactToListMapper {

    public constructor() {
        super(HubspotListIdsEnums.WHITE_PAPER_APPLINTH);
    }

    protected getListId(data: IBaseInput): number {
        if (data.properties.subscribed) {
            return 41;
        }
        return 47;
    }

}
