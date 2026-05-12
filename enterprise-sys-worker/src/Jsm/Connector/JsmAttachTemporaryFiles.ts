import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import JsmApplication from '../JsmApplication';

export const NAME = 'jsm-attach-temporary-files';

function escapeFilename(filename: string): string {
    return filename.replace(/"/g, '\\"').replace(/[\r\n]/g, '_');
}

export interface ITicketAttachment {
    filename: string;
    mimeType: string;
    base64: string;
}

export interface IInput {
    serviceDeskId: number;
    attachments: ITicketAttachment[];
    [k: string]: unknown;
}

export interface IOutput extends IInput {
    temporaryAttachmentIds: string[];
}

interface IAttachTemporaryFileResponse {
    temporaryAttachments: { temporaryAttachmentId: string; fileName: string }[];
}

/**
 * Uploads one or more files to JSM as temporary attachments. The returned
 * `temporaryAttachmentId`s are passed downstream so the create-request
 * connector can link them to the ticket in a single API call.
 *
 * If no attachments are present, the connector returns the payload unchanged
 * with an empty `temporaryAttachmentIds` array — i.e. no HTTP call is made.
 * This is the only allowed "skip" branch; once we send anything, it is exactly
 * one HTTP call regardless of how many files are uploaded.
 */
export default class JsmAttachTemporaryFiles extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const data = dto.getJsonData();
        checkParams(data as unknown as Record<string, unknown>, ['serviceDeskId']);

        const attachments = Array.isArray(data.attachments) ? data.attachments : [];
        if (attachments.length === 0) {
            return dto.setNewJsonData<IOutput>({ ...data, temporaryAttachmentIds: [] });
        }

        const application = this.getApplication<JsmApplication>();
        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const baseUrl = application.getBaseUrl(applicationInstall);
        const url = `${baseUrl}/rest/servicedeskapi/servicedesk/${data.serviceDeskId}/attachTemporaryFile`;

        const boundary = `----OrchestyJsmBoundary${Date.now()}`;
        const parts: Buffer[] = [];
        for (const attachment of attachments) {
            const fileBuffer = Buffer.from(attachment.base64, 'base64');
            const header = Buffer.from(
                `--${boundary}\r\n`
                + `Content-Disposition: form-data; name="file"; filename="${escapeFilename(attachment.filename)}"\r\n`
                + `Content-Type: ${attachment.mimeType || 'application/octet-stream'}\r\n\r\n`,
            );
            parts.push(header, fileBuffer, Buffer.from('\r\n'));
        }
        parts.push(Buffer.from(`--${boundary}--\r\n`));
        const multipartBody = Buffer.concat(parts);

        const baseRequest = application.getRequestDto(dto, applicationInstall, HttpMethods.POST, url);
        const headers = baseRequest.getHeaders();
        headers['Content-Type'] = `multipart/form-data; boundary=${boundary}`;
        delete headers['content-type'];
        // Atlassian requires this header on multipart uploads to bypass XSRF check.
        headers['X-Atlassian-Token'] = 'no-check';

        const request = new RequestDto(url, HttpMethods.POST, dto, multipartBody, headers);

        const response = await this.getSender().send(request, {
            success: [201],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        const body = response.getJsonBody() as IAttachTemporaryFileResponse;
        const temporaryAttachmentIds = (body.temporaryAttachments ?? []).map((t) => t.temporaryAttachmentId);

        return dto.setNewJsonData<IOutput>({ ...data, temporaryAttachmentIds });
    }

}
