export enum USEventType {
    APPLINTH_END_USER_APP_INSTALL = 10001,
    APPLINTH_END_USER_APP_UNINSTALL = 10002,
    CLOUD_INSTALL = 10003,
    CLOUD_UNINSTALL = 10004,
    ORCHESTY_OPERATIONS = 10005,
}

export function getCode(type: string): number {
    switch (type) {
        case 'applinth_enduser_app_install':
            return USEventType.APPLINTH_END_USER_APP_INSTALL;
        case 'applinth_enduser_app_uninstall':
            return USEventType.APPLINTH_END_USER_APP_UNINSTALL;
        case 'cloud_install':
            return USEventType.CLOUD_INSTALL;
        case 'cloud_uninstall':
            return USEventType.CLOUD_UNINSTALL;
        case 'orchesty_operations':
            return USEventType.ORCHESTY_OPERATIONS;
        default:
            throw new Error('Unsupported event type!');
    }
}

export interface USEvent {
    type: USEventType;
    created: Date;
    instanceId: string;
    data?: USEventData;
    version?: number;
}

export interface USEventData {
    appId?: string;
    endUserId?: string;
    total?: number;
    day?: string;
}
