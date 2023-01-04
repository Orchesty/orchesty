import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { IOrchestySales as IInput } from '../Interface/IOrchestySales';

export const NAME = 'orchesty-to-hubspot-mapper';

export default class OrchestyToHubSpotContactMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IInput | IOutput> {
        const { email, company, phone, ...res } = dto.getJsonData();

        if (!email) {
            dto.setStopProcess(ResultCode.DO_NOT_CONTINUE, 'Email is not defined');
            return dto;
        }

        return dto.setNewJsonData<IOutput>({ properties: {
            company: company ?? '',
            email,
            firstname: res['first-name'] ?? '',
            lastname: res['last-name'] ?? '',
            phone: phone ?? '',
        } });
    }

}

export interface IOutput {
    properties: {
        email: string;
        company?: string;
        firstname?: string;
        lastname?: string;
        phone?: string;
        website?: string;
    };
}
