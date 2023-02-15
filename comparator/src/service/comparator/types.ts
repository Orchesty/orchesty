export interface IConfiguration {
    idField: string;
    masterKey: string;
    excludedFields?: string[];
    stopOnEmptyArray?: boolean;
    ttl?: number;
    deleted?: boolean;
    totalCount?: number;
    isLast?: boolean;
    isBuffered?: boolean;
}

export interface IInput {
    items: Record<string, unknown>[];
    configuration: IConfiguration;
}

export interface IOutput {
    created: Record<string, unknown>[];
    updated: Record<string, unknown>[];
    deleted: string[];
}

export const emptyOutput: IOutput = { created: [], updated: [], deleted: [] };
