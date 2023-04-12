import { ParsedResult, RawEvent } from '../EventFactory';
import { eventSchema, USEventApplinthEndUserAppUnInstall, USEventType } from '../events';

export const TYPE = 'applinth_enduser_app_uninstall';

export function parse(data: RawEvent): ParsedResult {
    const { error } = eventSchema.validate(data);
    if (error) {
        throw error;
    }
    const event: USEventApplinthEndUserAppUnInstall = {
        created: data.created,
        instanceId: data.iid,
        type: USEventType.APPLINTH_END_USER_APP_UNINSTALL,
        data: {
            appId: data.data.aid,
            endUserId: data.data.euid,
        },
    };
    return {
        parsed: event,
    };
}
