import { IInput as IOutput, NAME as FLEXI_BEE_CREATE_FAKTURA_PRIJATA_PRILOHA_NAME } from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeCreateFakturaPrijataPrilohaConnector';
import { NAME as FLEXI_BEE_CREATE_ZAVAZEK_PRILOHA_NAME } from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeCreateZavazekPrilohaConnector';
import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { IOutput as IInput } from '@orchesty/connector-wflow/dist/Connector/WflowGetDocumentMainFileConnector';
import { NAME as WFLOW_APP_NAME } from '@orchesty/connector-wflow/dist/WflowApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { INVOICE_ID } from '../../FlexiBee/CustomNode/FlexiBeeFakturaPrijataToWflowDocumentMapper';
import { DOCUMENT_TYPE, KIND_INCOMING_INVOICE } from './WflowDocumentToFlexibeeFakturaPrijataMapper';

export const NAME = `${WFLOW_APP_NAME}-document-main-file-to-${FLEXI_BEE_APPLICATION}-faktura-prijata-priloha-mapper`;

export default class WflowDocumentMainFileToFlexiBeeFakturaPrijataPrilohaMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
        const { file } = dto.getJsonData();
        const id = String(dto.getHeader(INVOICE_ID));

        if (dto.getHeader(DOCUMENT_TYPE) === KIND_INCOMING_INVOICE) {
            return dto
                .setNewJsonData({ id, file })
                .setForceFollowers(FLEXI_BEE_CREATE_FAKTURA_PRIJATA_PRILOHA_NAME);
        }

        return dto
            .setNewJsonData({ id, file })
            .setForceFollowers(FLEXI_BEE_CREATE_ZAVAZEK_PRILOHA_NAME);
    }

}
