import BaseEntity from './BaseEntity';

export default interface Applinth extends BaseEntity {
    tenantId: string;
    instanceId: string;
    minPrice: number;
    minPriceDate: Date;
}
