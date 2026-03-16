import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { FlexiBeeFakturaPrijata } from '@orchesty/connector-flexi-bee/dist/types/FlexiBeeFakturaPrijata';
import { IInput as IOutput } from '@orchesty/connector-wflow/dist/Connector/WflowPutDocumentConnector';
import { NAME as WFLOW_APP_NAME } from '@orchesty/connector-wflow/dist/WflowApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { DOCUMENT_ID } from '../../Wflow/CustomNode/WflowWebhookPayloadMapper';

export const NAME = `${FLEXI_BEE_APPLICATION}-faktura-prijata-to-${WFLOW_APP_NAME}-document-mapper`;

export const INVOICE_ID = 'invoiceId';

export default class FlexiBeeFakturaPrijataToWflowDocumentMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<FlexiBeeFakturaPrijata>): ProcessDto<IOutput> {
        const documentId = dto.getHeader(DOCUMENT_ID) ?? '';
        const { id: tag, kod: internalCode } = dto.getJsonData();

        return dto.setNewJsonData({
            id: documentId,
            documentId,
            tag,
            internalCode,
        });
    }

}
