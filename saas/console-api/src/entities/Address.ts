import BaseEntity from './BaseEntity';

export default interface Address extends BaseEntity {
    tenantId: string;
    email: string;
    phone: string;
    firstname: string;
    surname: string;
    street: string;
    city: string;
    postalCode: string;
    companyName: string;
    countryId: string;
    identificationNumber: string;
    defaultInvoiceMaturity?: number | null;
    vatIdentificationNumber?: string | null;
    isRegisteredForVatOnPay?: boolean | null;
    isSendReminder?: boolean | null;
    title?: string | null;
    url?: string | null;
}
