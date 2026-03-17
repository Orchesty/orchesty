import { FlexiBeeTypFakturyPrijate } from '@orchesty/connector-flexi-bee/dist/Batch/FlexiBeeTypFakturyPrijateBatch';
import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { IInput as IOutput } from '@orchesty/connector-wflow/dist/Connector/WflowPatchSeriesConnector';
import { NAME as WFLOW_APP_NAME } from '@orchesty/connector-wflow/dist/WflowApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = `${FLEXI_BEE_APPLICATION}-typ-faktury-prijate-to-${WFLOW_APP_NAME}-series-mapper`;

export default class FlexiBeeTypFakturyPrijateToWflowSeriesMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<FlexiBeeTypFakturyPrijate[]>): ProcessDto<IOutput[]> {
        return dto.setNewJsonData(
            dto.getJsonData().map(({ kod: code, nazev: description }) => ({
                code, description, isValid: true, kind: 'IncomingInvoice',
            })),
        );
    }

}
