import unset from 'lodash.unset';
import { USEventApplinthEndUserAppInstall, USEventApplinthEndUserAppUnInstall } from '../events';
import { State } from '../processor';

export function applinthEndUserAppInstallHandler(event: USEventApplinthEndUserAppInstall, state: State): void {
    const endUser = state.endUsers[event.data.endUserId] ?? {};
    const lastAppInstallIds = endUser.lastAppInstallIds ?? {};

    if (event.data.appId in lastAppInstallIds) {
        // console.log('DUP install');
        // return;
        throw new Error(`Install event came for appId that's already marked as installed. EVENT: ${JSON.stringify(event)}, STATE: ${JSON.stringify(state)}`);
    }

    const installId = `AID${event.created.getTime()}`;

    // todo: check installId uniqueness

    state.endUsers = {
        ...state.endUsers,
        [event.data.endUserId]: {
            ...endUser,
            appInstalls: {
                ...state.endUsers[event.data.endUserId]?.appInstalls ?? {},
                [installId]: {
                    appId: event.data.appId,
                    start: event.created,
                    end: null,
                },
            },
            lastAppInstallIds: {
                ...lastAppInstallIds,
                [event.data.appId]: installId,
            },
        },
    };
}

export function applinthEndUserAppUnInstallHandler(event: USEventApplinthEndUserAppUnInstall, state: State): void {
    const euId = event.data.endUserId;
    const { appId } = event.data;
    const installId = state.endUsers[euId]?.lastAppInstallIds?.[appId] ?? undefined;

    if (!installId) {
        throw new Error(`Uninstall event came, but no appInstall exists to handle. EVENT: ${JSON.stringify(event)}, STATE: ${JSON.stringify(state)}`);
    }

    const endUser = state.endUsers[euId];

    endUser.appInstalls[installId].end = event.created;
    unset(endUser.lastAppInstallIds, appId);
}
