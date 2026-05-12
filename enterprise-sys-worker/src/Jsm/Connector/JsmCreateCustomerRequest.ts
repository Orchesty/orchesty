import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import JsmApplication from '../JsmApplication';

export const NAME = 'jsm-create-customer-request';

export interface IInput {
    serviceDeskId: number;
    requestTypeId: number;
    summary: string;
    description: string;
    raiseOnBehalfOf: string;
    [k: string]: unknown;
    temporaryAttachmentIds?: string[];
}

export interface IOutput {
    issueKey: string;
    issueId: string;
    serviceDeskId: number;
    requestTypeId: number;
    summary: string;
    description: string;
    raiseOnBehalfOf: string;
    temporaryAttachmentIds: string[];
    /** Carries the cloud user's e-mail forward to the confirmation step. */
    userEmail: string;
    userName: string;
    portalUrl?: string;
    accountName?: string;
    category?: string;
    categoryLabel?: string;
}

interface ICustomerRequestResponse {
    issueId: string;
    issueKey: string;
    _links?: { web?: string };
}

/**
 * Creates a customer request on the configured JSM Service Desk. If
 * `temporaryAttachmentIds` are present, they're attached as part of the
 * create payload via `requestFieldValues.attachment` (JSM Cloud accepts
 * this in a single call as long as the temporary IDs are valid and
 * uploaded for the same Service Desk).
 */
export default class JsmCreateCustomerRequest extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const data = dto.getJsonData();
        checkParams(
            data as unknown as Record<string, unknown>,
            ['serviceDeskId', 'requestTypeId', 'summary', 'description', 'raiseOnBehalfOf'],
        );

        const application = this.getApplication<JsmApplication>();
        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const baseUrl = application.getBaseUrl(applicationInstall);
        const url = `${baseUrl}/rest/servicedeskapi/request`;

        const requestFieldValues: Record<string, unknown> = {
            summary: data.summary,
            description: data.description,
        };
        if (data.temporaryAttachmentIds && data.temporaryAttachmentIds.length > 0) {
            requestFieldValues.attachment = data.temporaryAttachmentIds;
        }

        const body = {
            serviceDeskId: String(data.serviceDeskId),
            requestTypeId: String(data.requestTypeId),
            raiseOnBehalfOf: data.raiseOnBehalfOf,
            requestFieldValues,
        };

        const request = application.getRequestDto(dto, applicationInstall, HttpMethods.POST, url, body);

        const response = await this.getSender().send(request, {
            success: [201],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);

        const responseBody = response.getJsonBody() as ICustomerRequestResponse;

        /* eslint-disable-next-line no-underscore-dangle */
        const portalUrl = responseBody._links?.web;

        return dto.setNewJsonData<IOutput>({
            issueKey: responseBody.issueKey,
            issueId: responseBody.issueId,
            serviceDeskId: data.serviceDeskId,
            requestTypeId: data.requestTypeId,
            summary: data.summary,
            description: data.description,
            raiseOnBehalfOf: data.raiseOnBehalfOf,
            temporaryAttachmentIds: data.temporaryAttachmentIds ?? [],
            portalUrl,
            userEmail: data.raiseOnBehalfOf,
            userName: (data.userName as string | undefined) ?? '',
            accountName: data.accountName as string | undefined,
            category: data.category as string | undefined,
            categoryLabel: data.categoryLabel as string | undefined,
        });
    }

}
