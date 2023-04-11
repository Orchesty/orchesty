import { Collection, ObjectId } from 'mongodb';
import BillingV1 from './billing-handlers/BillingV1';
import { epochs } from './epochs';
import { USEvent } from './events';
import { logger, now } from './main';

// Double check you know what you are doing when modifying this
export const WORLD_START_YEAR = 2022;
export const WORLD_START_MONTH = 8;

export interface StateAppInstall {
    appId: string;
    start: Date;
    end: Date | null;
}

export interface StateEndUser {
    appInstalls: Record<string, StateAppInstall>;
    lastAppInstallIds: Record<string, string>;
}

export interface State {
    lastEvent: USEvent | null;
    endUsers: Record<string, StateEndUser>;
}

export class Processor {

    private state!: State;

    public async process(events: USEvent[]): Promise<void> {
        this.state = {
            lastEvent: null,
            endUsers: {},
        };

        for await (const event of events) {
            this.assertMonotonic(event);
            this.assertSameInstance(event);

            // TODO rich mozna se zrusi epochy, zamyslet se
            // todo: search through epochs
            const handlers = epochs[0].updates;

            if (handlers[event.type]) {
                // console.log([`processing ${event.type} using `, handlers[event.type]?.handler]);
                handlers[event.type]?.handler(event, this.state);
            } else {
                throw new Error(`no handler for ${JSON.stringify(event)}`);
            }
        }
        logger.debug({ msg: 'Final state:', state: this.state });
    }

    /**
     * @todo: implement more specific return type
     */
    public async flatten(
        collection: Collection,
        start: Date,
        end: Date,
        extraFields: object,
        applinthId: string,
        estimateTo: Date | null = null,
    ): Promise<unknown[]> {
        // computeCost changes state of the instance, so we have separate ones for cost and estimations.
        // I don't like it, it should be read-only
        // todo: search through billing-epochs
        const costHandler = new BillingV1();
        const estimationHandler = new BillingV1();

        const costs = [];

        const endUsers = this.state?.endUsers ?? {};
        for (const [endUserId, eu] of Object.entries(endUsers)) {
            for (const [installId, install] of Object.entries(eu.appInstalls)) {
                // The condition behaves correctly even in edge cases:
                // i.start == end: not included
                // i.start == i.end && i.start == end: zero length, not included here
                // i.start == i.end && i.start == start: zero length, included here
                // other edge cases assume (i.)start > (i.)end, these are invalid
                if (install.start < end && (install.end === null || install.end >= start)) {
                    let newStart = install.start;
                    let newEnd = install.end;
                    let isInstalled = false;

                    // clip the start date to interval start if it's crossed
                    if (newStart < start) {
                        newStart = start;
                    }

                    // clip the end date to interval end if it's crossed
                    if (newEnd === null || newEnd > end) {
                        newEnd = end;
                    }

                    // we set installed flag only on tail,
                    // the tail is magically detected by presence of estimateTo, todo: REFACTOR!
                    if (install.end === null && estimateTo !== null) {
                        isInstalled = true;
                    }

                    // eslint-disable-next-line no-await-in-loop
                    const module = await collection.findOne({
                        appName: install.appId,
                        applinthId,
                    });

                    let estimatedCost;
                    const cost = costHandler.computeCost(
                        this.state,
                        endUserId,
                        installId,
                        newStart,
                        newEnd,
                        module?.price,
                    );

                    if (estimateTo !== null) {
                        if (isInstalled) {
                            estimatedCost = estimationHandler.computeCost(
                                this.state,
                                endUserId,
                                installId,
                                newStart,
                                estimateTo,
                                module?.price,
                            );
                        } else {
                            // we have to recompute even for !isInstalled to be sure the estimator state is updated correctly
                            estimatedCost = estimationHandler.computeCost(
                                this.state,
                                endUserId,
                                installId,
                                newStart,
                                newEnd,
                                module?.price,
                            );
                        }
                    }

                    costs.push({
                        endUserId,
                        installId,
                        ...install,
                        start: newStart,
                        end: newEnd,
                        cost,
                        ...isInstalled && { installed: true },
                        ...estimateTo !== null && { estimatedCost },
                        ...extraFields,
                    });
                }
            }
        }
        return costs;
    }

    // TODO zkusit jestli funguje
    // bude potreba pro graf
    // pouzit pro daily a hourly, neni potreba resit ruzne dlouhe useky, jako u monthly
    // TODO rich zakomentovano
    // public* rangeWithGranularity(
    //     rangeStart: Date,
    //     rangeEnd: Date,
    //     granularityHours: number,
    //     extraFields: object,
    // ): Generator {
    //     const msRangeStart = rangeStart.getTime();
    //     const msRangeEnd = rangeEnd.getTime();
    //     const msInterval = granularityHours * 60 * 60 * 1000;
    //     const numBuckets = (msRangeEnd - msRangeStart) / msInterval;
    //
    //     if (numBuckets !== Math.ceil(numBuckets)) {
    //         throw Error(`The range and the granularity must result in an integer number of buckets, "${numBuckets}, ${rangeStart}, ${rangeEnd}" does not satisfy.`);
    //         // todo: fix
    //         // numBuckets = Math.ceil(numBuckets);
    //     }
    //
    //     for (let i = 0; i < numBuckets; i++) {
    //         const start = new Date(msRangeStart + i * msInterval);
    //         const end = new Date(msRangeStart + (i + 1) * msInterval);
    //         for (const doc of this.flatten(start, end, extraFields)) {
    //             logger.debug(doc);
    //             yield doc;
    //         }
    //     }
    // }

    public async monthlyAll(
        extraFields: { _id: ObjectId; tenantId: string; instanceId: string },
        collection: Collection,
    ): Promise<unknown[]> {
        const nowObj = new Date(now);
        const curYear = nowObj.getUTCFullYear();
        const curMonth = nowObj.getUTCMonth() + 1;

        // the World starts NOW!
        let year = WORLD_START_YEAR;
        let month = WORLD_START_MONTH;

        const costs = [];

        while (year < curYear || month <= curMonth) {
            const start = new Date(Date.UTC(year, month - 1, 1));

            // month+1 1st (because months are indexed from 0)
            const intervalEnd = new Date(Date.UTC(year, month, 1));

            let estimateTo = null;
            let end = intervalEnd;

            if (end > nowObj) {
                // cut current month at NOW and enable estimation
                end = nowObj;
                estimateTo = intervalEnd;
            }

            const basicExtraFields = { tenantId: extraFields.tenantId, instanceId: extraFields.instanceId };
            // eslint-disable-next-line no-await-in-loop
            for (const doc of await this.flatten(
                collection,
                start,
                end,
                basicExtraFields,
                String(extraFields._id),
                estimateTo,
            )) {
                // logger.debug(doc);
                costs.push(doc);
            }

            month++;
            if (month > 12) {
                year++;
                month = 1;
            }
        }

        return costs;
    }

    private assertMonotonic(event: USEvent): void {
        if (this.state?.lastEvent) {
            if (this.state.lastEvent.created >= event.created) {
                throw new Error('Event stream [created] field is not strictly monotonic! '
                    + `Event: ${JSON.stringify(event)}, lastEvent: ${JSON.stringify(this.state.lastEvent)}`);
            }
        }
        // last event not yet present, pass the test
    }

    private assertSameInstance(event: USEvent): void {
        if (this.state?.lastEvent) {
            if (this.state.lastEvent.instanceId !== event.instanceId) {
                throw new Error('Event stream [instanceId] field has no constant value! '
                    + `Event: ${JSON.stringify(event)}, lastEvent: ${JSON.stringify(this.state.lastEvent)}`);
            }
        }
        // last event not yet present, pass the test
    }

}
