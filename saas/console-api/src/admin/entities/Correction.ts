import BaseEntity from './BaseEntity';

export default interface Correction extends BaseEntity {
    tenantId: string;
    date: Date;
    hours: number;
    amount: number;
    note: string;
}
