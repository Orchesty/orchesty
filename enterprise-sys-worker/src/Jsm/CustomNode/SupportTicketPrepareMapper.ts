import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { ITicketAttachment } from '../Connector/JsmAttachTemporaryFiles';
import JsmApplication, { SupportCategory } from '../JsmApplication';

export const NAME = 'support-ticket-prepare';

const CATEGORY_LABELS: Record<SupportCategory, string> = {
    bug: 'Bug',
    question: 'Question',
    billing: 'Billing',
    /* eslint-disable @typescript-eslint/naming-convention */
    feature_request: 'Feature request',
    /* eslint-enable @typescript-eslint/naming-convention */
    other: 'Other',
};

const SUMMARY_MAX_LENGTH = 120;

export interface IInput {
    subject: string;
    description: string;
    category: SupportCategory;
    userEmail: string;
    userName?: string;
    accountId?: string;
    accountName?: string;
    cloudUserId?: string;
    frontendUrl?: string;
    attachments?: ITicketAttachment[];
}

export interface IOutput {
    serviceDeskId: number;
    requestTypeId: number;
    summary: string;
    description: string;
    raiseOnBehalfOf: string;
    attachments: ITicketAttachment[];
    userEmail: string;
    userName: string;
    category: SupportCategory;
    categoryLabel: string;
    accountId?: string;
    accountName?: string;
    frontendUrl?: string;
}

function buildSummary(subject: string, categoryLabel: string): string {
    const trimmed = subject.trim().replace(/\s+/g, ' ');
    const prefixed = `[${categoryLabel}] ${trimmed}`;
    if (prefixed.length <= SUMMARY_MAX_LENGTH) {
        return prefixed;
    }
    return `${prefixed.slice(0, SUMMARY_MAX_LENGTH - 1).trimEnd()}…`;
}

function buildDescription(data: IInput): string {
    const reporter = data.userName ? `${data.userName} <${data.userEmail}>` : data.userEmail;
    const account = data.accountName ?? data.accountId ?? '—';
    const cloudUserId = data.cloudUserId ?? '—';

    const footerLines = [
        '',
        '---',
        `Reported by: ${reporter}`,
        `Account: ${account}`,
        `Cloud user ID: ${cloudUserId}`,
    ];
    if (data.frontendUrl) {
        footerLines.push(`Origin: ${data.frontendUrl}`);
    }

    return `${data.description.trim()}\n\n${footerLines.join('\n')}`;
}

/**
 * Resolves the Service Desk and request type ID for a given support category
 * from the JSM application install, validates the cloud-side payload, and
 * builds the JSM `summary` (subject) and `description` body. The description
 * gets a small audit footer with the reporter's name, account, and a stable
 * cloud user ID so JSM agents always know exactly who filed the ticket and
 * from which workspace.
 */
export default class SupportTicketPrepareMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const data = dto.getJsonData();
        checkParams(
            data as unknown as Record<string, unknown>,
            ['subject', 'description', 'category', 'userEmail'],
        );

        const { category } = data;
        const categoryLabel = CATEGORY_LABELS[category];
        if (!categoryLabel) {
            dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                `Unknown support category "${category}". Allowed: ${Object.keys(CATEGORY_LABELS).join(', ')}.`,
            );
            return dto as unknown as ProcessDto<IOutput>;
        }

        const application = this.getApplication<JsmApplication>();
        const applicationInstall = await this.getApplicationInstallFromProcess(dto);

        let serviceDeskId: number;
        let requestTypeId: number;
        try {
            serviceDeskId = application.getServiceDeskId(applicationInstall);
            requestTypeId = application.getRequestTypeIdForCategory(applicationInstall, category);
        } catch (err) {
            dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                err instanceof Error ? err.message : String(err),
            );
            return dto as unknown as ProcessDto<IOutput>;
        }

        const summary = buildSummary(data.subject, categoryLabel);
        const description = buildDescription(data);

        return dto.setNewJsonData<IOutput>({
            serviceDeskId,
            requestTypeId,
            summary,
            description,
            raiseOnBehalfOf: data.userEmail,
            attachments: Array.isArray(data.attachments) ? data.attachments : [],
            userEmail: data.userEmail,
            userName: data.userName ?? '',
            category,
            categoryLabel,
            accountId: data.accountId,
            accountName: data.accountName,
            frontendUrl: data.frontendUrl,
        });
    }

}
