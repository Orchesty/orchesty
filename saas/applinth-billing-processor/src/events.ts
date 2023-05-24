export enum USEventType {
    APPLINTH_END_USER_APP_INSTALL = 10001,
    APPLINTH_END_USER_APP_UNINSTALL = 10002,
}

export interface USEvent {
    type: USEventType;
    created: Date;
    instanceId: string;
    data: { appId: string; endUserId: string };
    version?: number;
}
