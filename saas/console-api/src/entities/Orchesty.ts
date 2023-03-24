import { OrchestyVersion } from '../enums/OrchestyVersion';
import BaseEntity from './BaseEntity';

export default interface Orchesty extends BaseEntity {
    tenantId: string;
    instanceId: string;
    version: OrchestyVersion;
    price: number;
    startDate: Date;
}
