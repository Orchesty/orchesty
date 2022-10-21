import { IOrchestyCommunity } from './IOrchestyCommunity';

export interface IOrchestyContact extends IOrchestyCommunity {
    /* eslint-disable @typescript-eslint/naming-convention */
    company: string;
    phone: string;
    /* eslint-enable @typescript-eslint/naming-convention */
}
