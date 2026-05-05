export interface ISalesFormInput {
    form: string;
    submittedAt: string;
    source: string;
    locale: string;
    firstName: string;
    lastName: string;
    email: string;
    company: string;
    message: string;
    consent: boolean;
    phone?: string;
    jobTitle?: string;
    companySize?: string;
    meta?: { ip?: string; userAgent?: string };
}

export interface ISalesFormContext extends ISalesFormInput {
    orgId?: number;
    personId?: number;
    leadId?: string;
    leadUrl?: string;
    noteId?: number;
    businessEmailId?: number;
    customerEmailId?: number;
}
