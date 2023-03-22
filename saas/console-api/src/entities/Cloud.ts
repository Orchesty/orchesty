import { CloudPlan } from '../enums/CloudPlan';
import { Period } from '../enums/Period';
import BaseEntity from './BaseEntity';

export default interface Cloud extends BaseEntity {
    clientId?: string | null;
    plan?: CloudPlan | null;
    price?: number | null;
    period?: Period | null;
    startDate?: Date | null;
    closeDate?: Date | null;
}
