import JsonPlaceholderGetCommentListBatch from '@orchesty/nodejs-connectors/dist/lib/JsonPlaceholder/Batch/JsonPlaceholderGetCommentListBatch';
import { IOutput as IComment } from '@orchesty/nodejs-connectors/dist/lib/JsonPlaceholder/Connector/JsonPlaceholderGetCommentConnector';
import { IOutput as IInput } from '@orchesty/nodejs-connectors/dist/lib/JsonPlaceholder/Connector/JsonPlaceholderGetPostConnector';
import { NAME as JSON_PLACEHOLDER_APP_NAME } from '@orchesty/nodejs-connectors/dist/lib/JsonPlaceholder/JsonPlaceholderApplication';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { AUDIT_ENTITY } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';

export const NAME = `${JSON_PLACEHOLDER_APP_NAME}-get-post-comment-list-batch`;

export default class JsonPlaceholderGetPostCommentListBatch extends JsonPlaceholderGetCommentListBatch {

    public constructor() {
        super(true);
    }

    public getName(): string {
        return NAME;
    }

    public async processAction(
        dto: BatchProcessDto<IInput>,
    ): Promise<BatchProcessDto> {
        const post = dto.getJsonData();
        const newDto = await super.processAction(
            dto.setBridgeData(JSON.stringify({ postId: post.id })),
        );
        const comments: IOutput[] = JSON.parse(newDto.getMessages()[0].body);

        dto.setMessages([]);

        return dto.addItem({ ...post, comments }, undefined, undefined, {
            [AUDIT_ENTITY]: JSON.stringify({
                ...JSON.parse(dto.getHeader(AUDIT_ENTITY) ?? '{}'),
                comment: {
                    key: 'id',
                    fields: comments.map((comment) => ({ id: String(comment.id) })),
                },
            }),
        });
    }

}

export interface IOutput extends IInput {
    comments: IComment[];
}
