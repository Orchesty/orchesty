import { CloudPlan } from '../enums/CloudPlan';
import { Period } from '../enums/Period';
import BaseEntity from './BaseEntity';

export default interface Cloud extends BaseEntity {
    tenantId: string;
    plan: CloudPlan;
    price: number;
    period: Period;
    startDate: Date;
    closeDate?: Date | null;
}
