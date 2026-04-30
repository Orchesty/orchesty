import JsonPlaceholderGetCommentListBatch from '@orchesty/connector-json-placeholder/dist/Batch/JsonPlaceholderGetCommentListBatch';
import { IOutput as IComment } from '@orchesty/connector-json-placeholder/dist/Connector/JsonPlaceholderGetCommentConnector';
import { IOutput as IInput } from '@orchesty/connector-json-placeholder/dist/Connector/JsonPlaceholderGetPostConnector';
import { NAME as JSON_PLACEHOLDER_APP_NAME } from '@orchesty/connector-json-placeholder/dist/JsonPlaceholderApplication';
import AuditCheckpointRoleEnum from '@orchesty/nodejs-sdk/dist/lib/Commons/AuditCheckpointRoleEnum';
import { IAuditCheckpoint } from '@orchesty/nodejs-sdk/dist/lib/Commons/IAuditCheckpoint';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';

export const NAME = `${JSON_PLACEHOLDER_APP_NAME}-get-post-comment-list-batch`;

export default class JsonPlaceholderGetPostCommentListBatch extends JsonPlaceholderGetCommentListBatch {

    public constructor() {
        super(true);
    }

    public getName(): string {
        return NAME;
    }

    public getAuditCheckpoint(): IAuditCheckpoint {
        return {
            role: AuditCheckpointRoleEnum.PROCESS_STEP,
            fields: ['id'],
        };
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

        return dto.addItemWithAudit(
            { ...post, comments },
            'comment',
            'id',
            comments.map((comment) => ({ id: String(comment.id) })),
        );
    }

}

export interface IOutput extends IInput {
    comments: IComment[];
}
