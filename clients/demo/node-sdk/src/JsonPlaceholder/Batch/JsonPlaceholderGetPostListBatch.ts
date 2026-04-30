import BaseJsonPlaceholderGetPostListBatch, {
    IInput,
} from '@orchesty/connector-json-placeholder/dist/Batch/JsonPlaceholderGetPostListBatch';
import AuditCheckpointRoleEnum from '@orchesty/nodejs-sdk/dist/lib/Commons/AuditCheckpointRoleEnum';
import { IAuditCheckpoint } from '@orchesty/nodejs-sdk/dist/lib/Commons/IAuditCheckpoint';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';

export default class JsonPlaceholderGetPostListBatch extends BaseJsonPlaceholderGetPostListBatch {

    public getAuditCheckpoint(): IAuditCheckpoint {
        return {
            role: AuditCheckpointRoleEnum.PROCESS_ENTRY,
            fields: ['id', 'userId', 'title'],
        };
    }

    public async processAction(
        dto: BatchProcessDto<IInput>,
    ): Promise<BatchProcessDto> {
        const newDto = await super.processAction(dto.setBridgeData('{}'));
        const messages = newDto.getMessages();
        newDto.setMessages([]);

        for (const message of messages) {
            const data = JSON.parse(message.body) as IOutput;

            dto.addItemWithAudit(message.body, 'post', 'id', [{ id: String(data.id) }]);
        }

        return newDto;
    }

}

interface IOutput {
    id: number;
    userId: number;
    title: string;
    body: string;
}
