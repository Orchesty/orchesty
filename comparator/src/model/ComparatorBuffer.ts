export interface ComparatorBuffer {
    id: string;
    ttl: Date;
    pages: string[];
    key: string;
    data: Record<string, unknown>[];
    closed: boolean;
}
