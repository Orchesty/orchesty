import { IExtendedContact } from './IExtendedContact';

export interface ISales extends IExtendedContact {
    /* eslint-disable @typescript-eslint/naming-convention */
    'hosted-orchesty'?: boolean;
    applinth?: boolean;
    aaas?: boolean;
    team?: boolean;
    support?: boolean;
    course?: boolean;
    /* eslint-enable @typescript-eslint/naming-convention */
}
