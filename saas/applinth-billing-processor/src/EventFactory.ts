import unset from 'lodash.unset';
import { USEvent } from './events';
import { logger } from './main';
import * as install from './parsers/applinthEndUserAppInstall';
import * as uninstall from './parsers/applinthEndUserAppUnInstall';

export interface RawEvent {
    version: number;
    type: string;

    [key: string]: any; // eslint-disable-line @typescript-eslint/no-explicit-any
}

export interface ParsedResult {
    parsed: USEvent;
}

export interface UpgradedResult {
    upgraded: RawEvent;
}

/**
 * A parser knows either how to parse the RawEvent or how to upgrade it to the next version
 */
type ParserCallback = (data: RawEvent) => ParsedResult | UpgradedResult;

/**
 * The change can override a callback that propagated from prev. versions or remove it from the set (using `null` value)
 */
type EventVersionChangeSet = Record<string, ParserCallback | null>;

type EventParserMap = Record<string, ParserCallback>;

// version definitions

const eventVersions = new Map<number, EventVersionChangeSet>();

eventVersions.set(0, {
    [install.TYPE]: (data: RawEvent) => install.upgradeV0toV1(data),
    [uninstall.TYPE]: (data: RawEvent) => uninstall.parse(data),
});

eventVersions.set(1, {
    [install.TYPE]: (data: RawEvent) => install.parse(data),
});

// end version definitions

export class EventFactory {

    /**
     * Denormalized map of callbacks, generated form all EventVersionChangesets
     */
    public eventMatrix = new Map<number, EventParserMap>();

    public constructor() {
        this.buildEventMatrix();
    }

    public create(data: RawEvent): USEvent {
        let acc = data;
        // TODO rich zkusit foreach
        for (let i = 0; i < 100; i++) {
            const callback = this.eventMatrix.get(acc.version)?.[data.type];
            if (!callback) {
                logger.error(this.eventMatrix.entries());
                throw new Error(`No callback defined for ${acc.type} (v${acc.version})`);
            }

            const res = callback(acc);

            if ('parsed' in res) {
                return res.parsed;
            }
            acc = res.upgraded;
        }
        throw new Error(`Event upgrade chain probably dead looped. Last subject: ${acc.type} (v${acc.version})`);
    }

    private buildEventMatrix(): void {
        let lastRow: EventParserMap = {};

        for (const [v, changeSet] of eventVersions.entries()) {
            const map = { ...lastRow };
            this.eventMatrix.set(v, map);

            // iterate over changeset and delete/override existing callbacks
            for (const eventKey of Object.keys(changeSet)) {
                const def = changeSet[eventKey];
                if (def === null) {
                    unset(map, eventKey);
                } else {
                    map[eventKey] = def;
                }
            }
            lastRow = map;
        }
    }

}
