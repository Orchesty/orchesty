import BaseEntity from './BaseEntity';

export default interface Module extends BaseEntity {
    appName: string;
    applinthId: string;
    price: number;
    minPrice: number;
    minPriceDate: Date;
}
