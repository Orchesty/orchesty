import BaseEntity from './BaseEntity';

export default interface Correction extends BaseEntity {
    clientId?: string | null;
    date?: Date | null;
    hours?: number | null;
    amount?: number | null;
    note?: string | null;
}
