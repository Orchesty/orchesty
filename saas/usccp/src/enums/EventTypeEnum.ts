export enum EventTypeEnum {
    APPLINTH_END_USER_APP_INSTALL = 'applinth_enduser_app_install',
    APPLINTH_END_USER_APP_UNINSTALL = 'applinth_enduser_app_uninstall',
    CLOUD_INSTALL = 'cloud_install',
    CLOUD_UNINSTALL = 'cloud_uninstall',
    APPLINTH_END_USER_APP_HEARTHBEAT = 'applinth_enduser_app_hearthbeat',
    ORCHESTY_OPERATIONS = 'orchesty_operations',
}

export function getAllEventTypes(): EventTypeEnum[] {
    return [
        EventTypeEnum.APPLINTH_END_USER_APP_INSTALL,
        EventTypeEnum.APPLINTH_END_USER_APP_UNINSTALL,
        EventTypeEnum.CLOUD_INSTALL,
        EventTypeEnum.CLOUD_UNINSTALL,
        EventTypeEnum.APPLINTH_END_USER_APP_HEARTHBEAT,
        EventTypeEnum.ORCHESTY_OPERATIONS,
    ];
}
