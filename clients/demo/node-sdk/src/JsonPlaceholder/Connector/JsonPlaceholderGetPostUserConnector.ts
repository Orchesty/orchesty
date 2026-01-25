import JsonPlaceholderGetUserConnector, { IOutput as IUser } from '@orchesty/connector-json-placeholder/dist/Connector/JsonPlaceholderGetUserConnector';
import { NAME as JSON_PLACEHOLDER_APP_NAME } from '@orchesty/connector-json-placeholder/dist/JsonPlaceholderApplication';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IOutput as IInput } from '../Batch/JsonPlaceholderGetPostCommentListBatch';

export const NAME = `${JSON_PLACEHOLDER_APP_NAME}-get-post-user-connector`;

export default class JsonPlaceholderGetPostUserConnector extends JsonPlaceholderGetUserConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto> {
        const post = dto.getJsonData();
        const newDto = await super.processAction(
            dto.setNewJsonData({ id: post.userId }),
        );
        const user = newDto.getJsonData() as IOutput;

        return dto
            .setNewJsonData({ ...post, user })
            .addAuditHeader('user', 'id', [{
                id: String(user.id),
            }]);
    }

}

export interface IOutput extends IInput {
    user: IUser;
}
