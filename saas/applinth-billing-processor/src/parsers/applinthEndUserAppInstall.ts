import { fail } from 'assert';
import { ParsedResult, RawEvent, UpgradedResult } from '../EventFactory';
import { USEventApplinthEndUserAppInstall, USEventType } from '../events';
import { logger } from '../main';
import { ajv, applinthEndUserAppEventsV1 as validate } from '../validators';

export const TYPE = 'applinth_enduser_app_install';

export function parse(data: RawEvent): ParsedResult {
    if (!validate(data)) {
        logger.error(data);
        fail(ajv.errorsText(validate.errors));
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
