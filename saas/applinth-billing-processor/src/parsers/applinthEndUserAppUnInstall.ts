import { fail } from 'assert';
import { ParsedResult, RawEvent } from '../EventFactory';
import { USEventApplinthEndUserAppUnInstall, USEventType } from '../events';
import { ajv, applinthEndUserAppEventsV1 as validate } from '../validators';

export const TYPE = 'applinth_enduser_app_uninstall';

export function parse(data: RawEvent): ParsedResult {
    if (!validate(data)) {
        fail(ajv.errorsText(validate.errors));
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
