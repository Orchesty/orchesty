export interface ComparatorHash {
    id: string;
    masterKey: string;
    hash: string;
    externalId: string;
    ttl: Date | undefined;
}
