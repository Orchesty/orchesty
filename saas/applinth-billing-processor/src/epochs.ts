// todo: refactor, separate epoch definitions from interfaces
// todo: refactor separate and rename epochs/billing-epochs, now it evokes some association

// TODO rich pro urcovani verzi appInstall handleru

import { USEvent, USEventType } from './events';
import {
    applinthEndUserAppInstallHandler,
    applinthEndUserAppUnInstallHandler,
} from './handlers/applinthAppInstallHandlers';
import { State } from './processor';

export interface Epoch {

    /**
     * Increment by 100 in standard cases to be able to to sneak in more unexpected epochs without the need to renumber.
     */
    id: number;

    since: Date;

    /**
     * Updates of the event handing spec compared to the previous epoch.
     *
     * `null` value means the handling of specified event will be removed from the current epoch.
     */
    updates: {
        [key in USEventType]?: {
            handler(event: USEvent, state: State): void;
        } | null;
    };
}

export interface BillingHandler {
    computeCost(state: State, endUserId: string, appInstallId: string, start: Date, end: Date, price: number): number;
}

interface BillingEpoch {
    id: 100;
    since: Date;
    handler: BillingHandler;
}

export const epochs: Epoch[] = [
    {
        id: 100,
        since: new Date('2022-07-01'),
        updates: {
            [USEventType.APPLINTH_END_USER_APP_INSTALL]: {
                handler: applinthEndUserAppInstallHandler,
            },
            [USEventType.APPLINTH_END_USER_APP_UNINSTALL]: {
                handler: applinthEndUserAppUnInstallHandler,
            },
        },
    },
];
