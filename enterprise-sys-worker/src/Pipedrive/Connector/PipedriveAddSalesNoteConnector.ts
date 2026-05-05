import PipedriveAddNoteConnector, { IInput, IOutput } from '@orchesty/connector-pipedrive/dist/Connector/PipedriveAddNoteConnector';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { ISalesFormContext } from '../../Sales/types';

export const NAME = 'pipedrive-add-sales-note';

function escapeHtml(value: string): string {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function row(label: string, value?: string | null): string {
    if (!value) {
        return '';
    }
    return `<p><strong>${escapeHtml(label)}:</strong> ${escapeHtml(value)}</p>`;
}

export function buildSalesNoteHtml(ctx: ISalesFormContext): string {
    const fullName = `${ctx.firstName} ${ctx.lastName}`;
    const messageHtml = escapeHtml(ctx.message).replace(/\n/g, '<br/>');

    const sections = [
        '<h3>Sales Inquiry</h3>',
        row('Form', ctx.form),
        row('Submitted at', ctx.submittedAt),
        row('Source', ctx.source),
        row('Locale', ctx.locale),
        '<hr/>',
        '<h4>Contact</h4>',
        row('Name', fullName),
        row('Email', ctx.email),
        row('Phone', ctx.phone),
        row('Company', ctx.company),
        row('Job title', ctx.jobTitle),
        row('Company size', ctx.companySize),
        '<hr/>',
        '<h4>Message</h4>',
        `<blockquote>${messageHtml}</blockquote>`,
        '<hr/>',
        '<h4>Meta</h4>',
        row('IP', ctx.meta?.ip),
        row('User agent', ctx.meta?.userAgent),
        row('Consent', ctx.consent ? 'true' : 'false'),
    ];

    return sections.filter(Boolean).join('\n');
}

export default class PipedriveAddSalesNoteConnector extends PipedriveAddNoteConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto<IInput>): Promise<ProcessDto<IOutput>> {
        const ctxDto = dto as unknown as ProcessDto<ISalesFormContext>;
        const ctx = ctxDto.getJsonData();
        checkParams(ctx as unknown as Record<string, unknown>, ['leadId']);

        const noteInput: IInput = {
            content: buildSalesNoteHtml(ctx),
            lead_id: ctx.leadId,
        };

        dto.setNewJsonData<IInput>(noteInput);
        await super.processAction(dto);

        const noteOutput = dto.getJsonData() as unknown as IOutput;

        return ctxDto.setNewJsonData<ISalesFormContext>({
            ...ctx,
            noteId: noteOutput.id,
        }) as unknown as ProcessDto<IOutput>;
    }

}
