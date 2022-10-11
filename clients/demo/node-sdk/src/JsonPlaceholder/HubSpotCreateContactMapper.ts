import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

interface IInputJson {
    company: { name: string };
    email: string;
    name: string;
    website: string;
}

export default class HubSpotCreateContactMapper extends ACommonNode {

    public getName(): string {
        return 'hub-spot-create-contact-mapper';
    }

    public processAction(dto: ProcessDto<IInputJson>): ProcessDto | Promise<ProcessDto> {
        const body = dto.getJsonData();
        const name = body.name.split(' ');

        dto.setJsonData({
            properties: {
                company: body.company.name,
                email: body.email,
                firstname: name[0] ?? '',
                lastname: name[1] ?? '',
                website: body.website,
            },
        });

        return dto;
    }

}
