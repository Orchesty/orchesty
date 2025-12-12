import BaseJsonPlaceholderGetPostListBatch, {
    IInput,
} from '@orchesty/nodejs-connectors/dist/lib/JsonPlaceholder/Batch/JsonPlaceholderGetPostListBatch';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { AUDIT_ENTITY } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';

export default class JsonPlaceholderGetPostListBatch extends BaseJsonPlaceholderGetPostListBatch {

    public async processAction(
        dto: BatchProcessDto<IInput>,
    ): Promise<BatchProcessDto> {
        const newDto = await super.processAction(dto.setBridgeData('{}'));
        const messages = newDto.getMessages();
        newDto.setMessages([]);

        for (const message of messages) {
            const data = JSON.parse(message.body) as IOutput;

            dto.addItem(message.body, undefined, undefined, {
                [AUDIT_ENTITY]: JSON.stringify({ post: { key: 'id', fields: [{ id: String(data.id) }] } }),
            });
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
