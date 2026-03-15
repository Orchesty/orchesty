import { IInput as IOutput } from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeCreateFakturaPrijataPrilohaConnector';
import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { NAME as WFLOW_APP_NAME } from '@orchesty/connector-wflow/dist/WflowApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { INVOICE_ID } from '../../FlexiBee/CustomNode/FlexiBeeFakturaPrijataMapper';

export const NAME = `${WFLOW_APP_NAME}-document-main-file-to-${FLEXI_BEE_APPLICATION}-faktura-prijata-priloha-mapper`;

export default class WflowDocumentMainFileToFlexiBeeFakturaPrijataPrilohaMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto): ProcessDto<IOutput> {
        return dto.setNewJsonData({
            id: String(dto.getHeader(INVOICE_ID)),
            content: Buffer.from(dto.getData()).toString('base64'),
        });
    }

}
