import BaseEntity from './BaseEntity';

export default interface Client extends BaseEntity {
    companyName?: string | null;
    iDokladId?: string | null;
    contact?: IContact[];
    hourlyRate?: number | null;
    note?: string | null;
}

export interface IContact {
    name?: string | null;
    email?: string | null;
    phone?: string | null;
}
