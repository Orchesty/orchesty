import Joi from 'joi';

export enum USEventType {
    APPLINTH_END_USER_APP_INSTALL = 10001,
    APPLINTH_END_USER_APP_UNINSTALL = 10002,
}

export const eventSchema = Joi.object({
    type: Joi.string().valid('applinth_enduser_app_install', 'applinth_enduser_app_uninstall').required(),
    created: Joi.date().required(),
    iid: Joi.string().required(),
    data: Joi.object({
        aid: Joi.string().required(),
        euid: Joi.string().required(),
    }).required(),
    version: Joi.number().required(),
});

export interface USEvent {
    type: USEventType;
    created: Date;
    instanceId: string;
    data: Record<string, unknown>;
    version?: number;
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
