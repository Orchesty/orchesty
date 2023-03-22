import BaseEntity from './BaseEntity';

export default interface Applinth extends BaseEntity {
    clientId?: string | null;
    cloudId?: string | null;
    startDate?: Date | null;
    minPrice?: number | null;
    minPriceDate?: Date | null;
}
