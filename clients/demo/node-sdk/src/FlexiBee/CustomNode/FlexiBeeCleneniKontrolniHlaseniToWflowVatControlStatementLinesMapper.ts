import { FlexiBeeCleneniKontrolniHlaseni } from '@orchesty/connector-flexi-bee/dist/Batch/FlexiBeeCleneniKontrolniHlaseniBatch';
import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { IInput as IOutput } from '@orchesty/connector-wflow/dist/Connector/WflowPatchVatControlStatementLinesConnector';
import { NAME as WFLOW_APP_NAME } from '@orchesty/connector-wflow/dist/WflowApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';

export const NAME = `${FLEXI_BEE_APPLICATION}-cleneni-kontrolni-hlaseni-to-${WFLOW_APP_NAME}-vat-control-statement-lines-mapper`;

export default class FlexiBeeCleneniKontrolniHlaseniToWflowVatControlStatementLinesMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<FlexiBeeCleneniKontrolniHlaseni[]>): ProcessDto<IOutput[]> {
        const items = dto.getJsonData();
        const czItems = items.filter((item) => item.stat.includes('code:CZ'));

        if (czItems.length === 0) {
            dto.setStopProcess(
                ResultCode.DO_NOT_CONTINUE,
                `Items ${items.map((i) => i.kod).join(', ')} could not be sent because they have ${items.map((i) => i.stat).join(', ')} countries.`,
            );

            return dto;
        }

        return dto.setNewJsonData(
            czItems.map(({ kod: code, nazev: description }) => ({ code, description, isValid: true })),
        );
    }

}
