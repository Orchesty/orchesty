import { IOutput as ICreateOutput } from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeCreateFakturaPrijataConnector';
import { IInput as IGetInput } from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeGetFakturaPrijataConnector';
import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { DOCUMENT_ID } from '../../Wflow/CustomNode/WflowWebhookPayloadMapper';
import { INVOICE_ID } from './FlexiBeeFakturaPrijataToWflowDocumentMapper';

export const NAME = `${FLEXI_BEE_APPLICATION}-create-faktura-prijata-to-${FLEXI_BEE_APPLICATION}-get-faktura-prijata-mapper`;

export default class FlexiBeeCreateFakturaPrijataToFlexiBeeGetFakturaPrijataMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<ICreateOutput[]>): ProcessDto<IGetInput> {
        const documentId = dto.getHeader(DOCUMENT_ID) ?? '';
        const { id } = dto.getJsonData().find((r) => r['request-id'] === `ext:${documentId}`) ?? {};

        return dto
            .setNewJsonData({ id: String(id) })
            .addHeader(INVOICE_ID, String(id));
    }

}
