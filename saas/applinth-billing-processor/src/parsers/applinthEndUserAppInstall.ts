import { ParsedResult, RawEvent, UpgradedResult } from '../EventFactory';
import { eventSchema, USEventApplinthEndUserAppInstall, USEventType } from '../events';

export const TYPE = 'applinth_enduser_app_install';

export function parse(data: RawEvent): ParsedResult {
    const { error } = eventSchema.validate(data);
    if (error) {
        throw error;
    }
    const event: USEventApplinthEndUserAppInstall = {
        created: data.created,
        instanceId: data.iid,
        type: USEventType.APPLINTH_END_USER_APP_INSTALL,
        data: {
            appId: data.data.aid,
            endUserId: data.data.euid,
        },
    };
    return {
        parsed: event,
    };
}

export function upgradeV0toV1(data: RawEvent): UpgradedResult {
    return {
        upgraded: {
            ...data,
            version: 1,
        },
    };
}
