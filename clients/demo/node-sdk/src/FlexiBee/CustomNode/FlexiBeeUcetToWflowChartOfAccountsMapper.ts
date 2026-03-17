import { FlexiBeeUcet } from '@orchesty/connector-flexi-bee/dist/Batch/FlexiBeeUcetBatch';
import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { IInput as IOutput } from '@orchesty/connector-wflow/dist/Connector/WflowPatchChartOfAccountsConnector';
import { NAME as WFLOW_APP_NAME } from '@orchesty/connector-wflow/dist/WflowApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = `${FLEXI_BEE_APPLICATION}-ucet-to-${WFLOW_APP_NAME}-chart-of-accounts-mapper`;

export default class FlexiBeeUcetToWflowChartOfAccountsMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<FlexiBeeUcet[]>): ProcessDto<IOutput[]> {
        return dto.setNewJsonData(
            dto.getJsonData().map(({ kod: code, nazev: description }) => ({ code, description, isValid: true })),
        );
    }

}
