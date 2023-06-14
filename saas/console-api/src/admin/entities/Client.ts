import BaseEntity from './BaseEntity';

export default interface Client extends BaseEntity {
    companyName: string;
    tenantId: string;
    contact: IContact[];
    supportHourlyRate: number;
    supportSubscription: number;
    supportResponseTime: number;
    invoicingId?: string | null;
    hourlyRate?: number | null;
    note?: string | null;
}

export interface IContact {
    name?: string | null;
    email?: string | null;
    phone?: string | null;
}
