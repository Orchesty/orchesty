export enum ResourceEnum {
    LIST_USAGE_STATS = 'list_usage_stats',

    USERS_LIST_ALL = 'users_list_all',
    USERS_SEARCH = 'users_search',
    GET_USER = 'get_user',
    CREATE_USER = 'create_user',
    UPDATE_USER = 'update_user',
    DELETE_USER = 'delete_user',
    GENERATE_RESET_PASSWORD_LINK = 'generate_reset_password_link',
    USE_ANOTHER_TENANT_ID = 'use_another_tenant_id',

    TENANTS_LIST_ALL = 'tenants_list_all',
    GET_TENANT = 'get_tenant',
    CREATE_TENANT = 'create_tenant',
    UPDATE_TENANT = 'update_tenant',
    DELETE_TENANT = 'delete_tenant',
}

export function getAllResources(): ResourceEnum[] {
    return [
        ResourceEnum.LIST_USAGE_STATS,
        ResourceEnum.USERS_LIST_ALL,
        ResourceEnum.USERS_SEARCH,
        ResourceEnum.GET_USER,
        ResourceEnum.CREATE_USER,
        ResourceEnum.UPDATE_USER,
        ResourceEnum.DELETE_USER,
        ResourceEnum.GENERATE_RESET_PASSWORD_LINK,
        ResourceEnum.USE_ANOTHER_TENANT_ID,
        ResourceEnum.TENANTS_LIST_ALL,
        ResourceEnum.GET_TENANT,
        ResourceEnum.CREATE_TENANT,
        ResourceEnum.UPDATE_TENANT,
        ResourceEnum.DELETE_TENANT,
    ];
}
