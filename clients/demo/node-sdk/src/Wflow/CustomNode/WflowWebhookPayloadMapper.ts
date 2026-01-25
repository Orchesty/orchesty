import { IInput as IOutput } from '@orchesty/connector-wflow/dist/Connector/WflowGetDocumentConnector';
import { NAME as WFLOW_APP_NAME, WebhookType } from '@orchesty/connector-wflow/dist/WflowApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = `${WFLOW_APP_NAME}-webhook-payload-mapper`;
export const DOCUMENT_ID = 'documentId';

export default class WflowWebhookPayloadMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
        const { notification: { documentId } } = dto.getJsonData();
        dto.addHeader(DOCUMENT_ID, documentId);

        return dto.setNewJsonData({ documentId });
    }

}

export interface IInput {
    notification: {
        organization: string;
        action: Exclude<WebhookType, WebhookType.ALL>;
        documentId: string;
    };
    registrationId: string;
    description: string;
    id: string;
}
