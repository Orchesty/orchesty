import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { ISales as IInput } from '../Interface/ISales';

export const NAME = 'hanaboso-to-hubspot-mapper';

export default class HanabosoHubSpotContactMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IInput | IOutput> {
        const { email, company, phone, language, ...res } = dto.getJsonData();

        if (!email) {
            dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Email is not defined');
            return dto;
        }

        return dto.setNewJsonData<IOutput>({ properties: {
            company: company ?? '',
            email,
            firstname: res['first-name'] ?? '',
            lastname: res['last-name'] ?? '',
            phone: phone ?? '',
            subscribed: res.subscribed ?? false,
            language,
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
        subscribed?: boolean;
        language?: string;
    };
}
