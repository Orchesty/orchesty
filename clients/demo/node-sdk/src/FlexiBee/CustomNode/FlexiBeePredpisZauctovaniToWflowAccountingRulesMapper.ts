import { FlexiBeePredpisZauctovani } from '@orchesty/connector-flexi-bee/dist/Batch/FlexiBeePredpisZauctovaniBatch';
import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { IInput as IOutput } from '@orchesty/connector-wflow/dist/Connector/WflowPatchAccountingRulesConnector';
import { NAME as WFLOW_APP_NAME } from '@orchesty/connector-wflow/dist/WflowApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = `${FLEXI_BEE_APPLICATION}-predpis-zauctovani-to-${WFLOW_APP_NAME}-accounting-rules-mapper`;

export default class FlexiBeePredpisZauctovaniToWflowAccountingRulesMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<FlexiBeePredpisZauctovani[]>): ProcessDto<IOutput[]> {
        return dto.setNewJsonData(
            dto.getJsonData().map(({ kod: code, nazev: description }) => ({
                code, description, isValid: true, kind: 'IncomingInvoice',
            })),
        );
    }

}
