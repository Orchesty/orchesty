export enum USEventType {
    APPLINTH_END_USER_APP_INSTALL = 10001,
    APPLINTH_END_USER_APP_UNINSTALL = 10002,
}

export interface USEvent {
    type: USEventType;
    created: Date;
    instanceId: string;
    data: Record<string, unknown>;
}

export type USEventApplinthEndUserAppInstall = USEvent & {
    type: USEventType.APPLINTH_END_USER_APP_INSTALL;
    data: {
        appId: string;
        endUserId: string;
    };
};

export type USEventApplinthEndUserAppUnInstall = USEvent & {
    type: USEventType.APPLINTH_END_USER_APP_UNINSTALL;
    data: {
        appId: string;
        endUserId: string;
    };
};
