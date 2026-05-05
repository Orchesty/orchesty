export interface ISalesFormInput {
    form: string;
    submittedAt: string;
    source: string;
    locale: string;
    firstName: string;
    lastName: string;
    email: string;
    phone?: string;
    company: string;
    jobTitle?: string;
    companySize?: string;
    message: string;
    consent: boolean;
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
