import { IBaseContact } from './IBaseContact';

export interface IExtendedContact extends IBaseContact {
    /* eslint-disable @typescript-eslint/naming-convention */
    company: string;
    phone: string;
    /* eslint-enable @typescript-eslint/naming-convention */
}
