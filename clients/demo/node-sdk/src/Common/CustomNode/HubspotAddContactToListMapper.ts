import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { IInput as IOuput } from '../Connector/HubSpotAddEmailToListConnector';
import { getHubspotListName, HubspotListIdsEnums } from '../Enum/HubspotListIdsEnums';
import { IOutput as IInput } from './HanabosoHubSpotContactMapper';

export default class HubspotAddContactToListMapper extends ACommonNode {

  public constructor(protected readonly hubSpotList: HubspotListIdsEnums) {
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

    return dto.setNewJsonData<IOuput>({
      emails: [properties.email],
      language: properties.language,
      listId: this.getListId(dto.getJsonData()),
    });
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected getListId(data: IInput): number {
    return this.hubSpotList;
  }

}
