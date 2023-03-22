import BaseEntity from './BaseEntity';

export default interface Support extends BaseEntity {
    clientId?: string | null;
    hourlyRate?: number | null;
    subscription?: number | null;
    responseTime?: number | null;
}
