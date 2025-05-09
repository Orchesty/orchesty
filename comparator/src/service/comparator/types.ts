export interface IConfiguration {
    idField: string;
    masterKey: string;
    passAsListOfExistingItems?: boolean;
    excludedFields?: string[];
    stopOnEmptyArray?: boolean;
    ttl?: number;
    skipComparison?: boolean;
    lock?: boolean;
    deleted?: boolean;
    totalCount?: number;
    isLast?: boolean;
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
