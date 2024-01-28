import { readFileSync } from 'fs';
import HubspotToSesEmailMapper, { IInput } from '../../Common/CustomNode/HubspotToSesEmailMapper';
import { getPageName } from '../../Common/Enum/PageEnum';

export default class HubspotWhiterPaperToSesEmailMapper extends HubspotToSesEmailMapper {

    protected getContent(data: IInput): string {
        return readFileSync(`${__dirname}/Templates/${getPageName(this.page)}-${data.language}.html`).toString();
    }

}
