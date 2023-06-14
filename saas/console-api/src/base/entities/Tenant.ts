import { auth } from 'firebase-admin';
import GTenant = auth.Tenant;

export default interface Tenant {
    instances: IInstance[];
    tenantId: string;
    gTenantId: string;
    gTenant?: GTenant;
}

export interface IInstance {
    instanceId: string;
}
