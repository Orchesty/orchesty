import { ISalesFormContext } from './types';

export interface IMergeVar {
    name: string;
    content: string;
}

// Ecomail merge tags are referenced in templates as `*|NAME|*`. The platform also
// silently injects a set of default tags (e.g. EMAIL, NAME, SURNAME, …); to avoid
// any collision with those defaults — and to make our tags trivially recognizable
// in templates — we uppercase every name and prefix it with `ORCH_`.
const PREFIX = 'ORCH_';

function tag(name: string, content: string): IMergeVar {
    return { name: `${PREFIX}${name.toUpperCase()}`, content };
}

export function buildSalesMergeVars(ctx: ISalesFormContext): IMergeVar[] {
    return [
        tag('form', ctx.form),
        tag('submitted_at', ctx.submittedAt),
        tag('source', ctx.source),
        tag('locale', ctx.locale),
        tag('first_name', ctx.firstName),
        tag('last_name', ctx.lastName),
        tag('full_name', `${ctx.firstName} ${ctx.lastName}`),
        tag('email', ctx.email),
        tag('phone', ctx.phone ?? ''),
        tag('company', ctx.company),
        tag('job_title', ctx.jobTitle ?? ''),
        tag('company_size', ctx.companySize ?? ''),
        tag('message', ctx.message),
        tag('org_id', ctx.orgId !== undefined ? String(ctx.orgId) : ''),
        tag('person_id', ctx.personId !== undefined ? String(ctx.personId) : ''),
        tag('lead_id', ctx.leadId ?? ''),
        tag('lead_url', ctx.leadUrl ?? ''),
        tag('note_id', ctx.noteId !== undefined ? String(ctx.noteId) : ''),
        tag('ip', ctx.meta?.ip ?? ''),
        tag('user_agent', ctx.meta?.userAgent ?? ''),
    ];
}
