export interface MySqlDocument {
    id: string;
    type: EntityType;
    entityId: number;
    externalId: string;
}

export enum EntityType {
    CATEGORY = 'category',
    PRODUCT = 'product',
    PRODUCT_CATEGORY = 'product_category',
}
