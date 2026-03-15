import { IOutput as IInput } from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeCreateFakturaPrijataConnector';
import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { IInput as IOutput } from '@orchesty/connector-wflow/dist/Connector/WflowPutDocumentConnector';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { DOCUMENT_ID } from '../../Wflow/CustomNode/WflowWebhookPayloadMapper';

export const NAME = `${FLEXI_BEE_APPLICATION}-faktura-prijata-mapper`;

export const INVOICE_ID = 'invoiceId';

export default class FlexiBeeFakturaPrijataMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput[]>): ProcessDto<IOutput> {
        const id = dto.getHeader(DOCUMENT_ID) ?? '';
        const { id: tag } = dto.getJsonData().find((result) => result['request-id'] === `ext:${id}`) ?? {};

        return dto.setNewJsonData({
            id,
            documentId: id,
            tag,
        }).addHeader(INVOICE_ID, String(tag));
    }

}
