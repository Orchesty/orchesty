import { NAME as BEECEPTOR_APP_NAME } from '@orchesty/connector-beeceptor/dist/BeeceptorApplication';
import { NAME as JSON_PLACEHOLDER_APP_NAME } from '@orchesty/connector-json-placeholder/dist/JsonPlaceholderApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { IOutput as IInput } from '../Connector/JsonPlaceholderGetPostUserConnector';

export const NAME = `${JSON_PLACEHOLDER_APP_NAME}-to-${BEECEPTOR_APP_NAME}-sync-post-mapper`;

export default class JsonPlaceholderToBeeceptorSyncPostMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
        const post = dto.getJsonData();
        return dto.setNewJsonData({
            title: post.title,
            content: post.body,
            user: {
                email: post.user.email,
            },
            comments: post.comments.map((comment) => ({ content: comment.body, user: { email: comment.email } })),
        });
    }

}

export interface IOutput {
    title: string;
    content: string;
    user: {
        email: string;
    };
    comments: {
        content: string;
        user: {
            email: string;
        };
    }[];
}
