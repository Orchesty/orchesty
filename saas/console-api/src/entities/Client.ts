export default interface Client {
    companyName?: string | null;
    iDokladId?: string | null;
    contact?: IContact[];
    hourlyRate?: number | null;
    note?: string | null;
    id?: string | null;
}

export interface IContact {
    name?: string | null;
    email?: string | null;
    phone?: string | null;
}
