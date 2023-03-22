import { OrchestyVersion } from '../enums/OrchestyVersion';
import BaseEntity from './BaseEntity';

export default interface Orchesty extends BaseEntity {
    clientId?: string | null;
    cloudId?: string | null;
    version?: OrchestyVersion | null;
    price?: number | null;
    startDate?: Date | null;
}
