export interface IBaseContact {
    /* eslint-disable @typescript-eslint/naming-convention */
    'first-name': string;
    'last-name': string;
    email: string;
    message: string;
    language?: string;
    subscribed?: boolean;

    /* eslint-enable @typescript-eslint/naming-convention */
}
