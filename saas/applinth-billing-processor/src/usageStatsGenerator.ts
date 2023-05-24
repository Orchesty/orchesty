import { Collection, ObjectId, UpdateFilter } from 'mongodb';
import { UsageStatsType } from './enums/UsageStatsType';
import { USEvent, USEventType } from './events';
import { logger } from './index';
import Mongo, { CollectionEnum } from './storage/mongo/Mongo';
import TimeModule from './TimeModule';

export class UsageStatsGenerator {

    private readonly currentDateKey: string;

    public constructor(private readonly usageStatsCollection: Collection, private readonly timeModule: TimeModule) {
        const date = new Date(this.timeModule.getNow());
        this.currentDateKey = date.toISOString().slice(0, 7);
    }

    public async generateForApplinths(
        applinths: IApplinth[],
        colMetadata: Collection,
        colMonthly: Collection,
        colModule: Collection,
        mongo: Mongo,
    ): Promise<void> {
        for (const applinth of applinths) {
            const metadata = (await colMetadata.findOne(
                { tenantId: applinth.tenantId },
            ))?.instances[applinth.instanceId];

            const lastHighestDate = metadata ? metadata.lastRunHighestEventDate : null;

            const coll = mongo.getUsageStatsCollection(CollectionEnum.EVENTS);
            const res = await coll.find({ iid: applinth.instanceId }).sort({ created: 1 }).limit(1).toArray();
            const historyStart = res.length ? new Date(res[0].created / 1000) : null;

            const highestDate = await this.generateMonthlyStats(colModule, coll, applinth, lastHighestDate);

            await colMetadata.updateOne({ tenantId: applinth.tenantId }, {
                $set: {
                    [`instances.${applinth.instanceId}.billingHistoryStart`]: historyStart,
                    [`instances.${applinth.instanceId}.billingHistoryEnd`]: new Date(this.timeModule.getNow()),
                    [`instances.${applinth.instanceId}.lastRunHighestEventDate`]: highestDate,
                },
            }, { upsert: true });
        }
    }

    private async generateMonthlyStats(
        moduleCollection: Collection,
        eventsCollection: Collection,
        applinth: IApplinth,
        lastHighestDate: Date | null,
    ): Promise<Date | null> {
        const { tenantId, instanceId } = applinth;
        const applinthId = String(applinth._id);
        const currentDate = new Date(this.timeModule.getNow());

        let highestDate = lastHighestDate;

        for (const module of await moduleCollection.find({ applinthId }).toArray()) {
            const minPrice = module.minPrice ?? applinth.minPrice;
            const minPriceDate = module.minPriceDate ?? applinth.minPriceDate;

            const installed: IUsageStatsMonthly[] = await this.usageStatsCollection.find({
                installed: true,
                instanceId,
                end: { $lt: currentDate },
                appId: module.appName,
            }).toArray() as IUsageStatsMonthly[];

            highestDate = await this.generateMonthlyStatsForModule(
                minPrice,
                minPriceDate,
                currentDate,
                eventsCollection,
                applinth,
                module.appName,
                module.price,
                lastHighestDate,
                highestDate,
                installed,
                instanceId,
                tenantId,
            );
        }

        return highestDate;
    }

    private async generateMonthlyStatsForModule(
        minPrice: number,
        minPriceDate: Date,
        currentDate: Date,
        eventsCollection: Collection,
        applinth: IApplinth,
        appName: string,
        price: number,
        lastHighestDate: Date | null,
        highestDate: Date | null,
        installed: IUsageStatsMonthly[],
        instanceId: string,
        tenantId: string,
    ): Promise<Date | null> {
        let minPriceUsageStats: Record<string, number> | null = null;
        const checkDate = new Date(this.timeModule.getNow());
        checkDate.setDate(1);
        checkDate.setMonth(currentDate.getMonth() - 1);
        checkDate.setHours(0, 0, 0, 0);
        if (minPrice > 0 && minPriceDate <= checkDate) {
            minPriceUsageStats = {};
        }

        const events = await this.getEvents(eventsCollection, applinth.instanceId, appName, lastHighestDate);
        logger.info(`Found ${events.length} new events for instanceId ${applinth.instanceId} and appName ${appName}`);

        if (events.length) {
            const newestEvent = events[events.length - 1];

            if (!highestDate || highestDate < newestEvent.created) {
                highestDate = newestEvent.created;
            }
        }
        for (const event of events) {
            const install = installed
                .findLast((i) => i.endUserId === event.data.endUserId && i.appId === event.data.appId);

            if (install) {
                if (event.type === USEventType.APPLINTH_END_USER_APP_INSTALL) {
                    const iEnd: Date = new Date(install.end);
                    const eCreate = new Date(event.created);

                    if (
                        install.installed === false
                        && iEnd.setHours(0, 0, 0, 0) === eCreate.setHours(0, 0, 0, 0)
                    ) {
                        install.end = currentDate;
                        install.installed = true;
                    } else {
                        installed.push(this.generateUsageStat(
                            event,
                            currentDate,
                            instanceId,
                            tenantId,
                            true,
                            UsageStatsType.NORMAL,
                        ));
                    }
                } else {
                    install.end = event.created;
                    install.installed = false;
                }
            } else if (event.type === USEventType.APPLINTH_END_USER_APP_INSTALL) {
                installed.push(this.generateUsageStat(
                    event,
                    currentDate,
                    instanceId,
                    tenantId,
                    true,
                    UsageStatsType.NORMAL,
                ));
            }
        }

        for (const install of installed) {
            if (install.installed) {
                install.end = currentDate;
            }

            await this.saveUsageStat(install, price, minPriceUsageStats);
        }

        if (minPriceUsageStats) {
            await this.generateMinPriceDiff(minPriceUsageStats, minPrice, applinth, appName);
        }

        return highestDate;
    }

    private async generateMinPriceDiff(
        minPriceUsageStats: Record<string, number>,
        minPrice: number,
        applinth: IApplinth,
        appName: string,
    ): Promise<void> {
        for (const [key, value] of Object.entries(minPriceUsageStats)) {
            const startDate = new Date(key);
            if (value < minPrice) {
                const minPriceEvent = await this.usageStatsCollection.findOne({
                    type: UsageStatsType.MIN_PRICE_DIFF,
                    instanceId: applinth.instanceId,
                    appId: appName,
                    start: startDate,
                    tenantId: applinth.tenantId,
                });

                const diff = minPrice - value;
                if (minPriceEvent && minPriceEvent.cost !== diff) {
                    minPriceEvent.cost = diff;
                    await this.usageStatsCollection.updateOne({ _id: minPriceEvent._id }, { price: diff });
                } else {
                    const endDate = new Date(key);
                    endDate.setMonth(endDate.getMonth() + 1);
                    await this.usageStatsCollection.insertOne({
                        appId: appName,
                        cost: diff,
                        end: endDate,
                        instanceId: applinth.instanceId,
                        start: startDate,
                        tenantId: applinth.tenantId,
                        type: UsageStatsType.MIN_PRICE_DIFF,
                    });
                }
            }
        }
    }

    private generateUsageStat(
        event: USEvent,
        end: Date,
        instanceId: string,
        tenantId: string,
        installed: boolean,
        type: UsageStatsType,
    ): IUsageStatsMonthly {
        const { data, created: start } = event;
        const { appId, endUserId } = data;

        return {
            appId,
            start,
            end,
            endUserId,
            type,
            installId: `AID${start.getTime()}`,
            instanceId,
            tenantId,
            installed,
        };
    }

    private async saveUsageStat(
        install: IUsageStatsMonthly,
        price: number,
        minPriceUsageStats: Record<string, number> | null,
    ): Promise<IUsageStatsMonthly | null> {
        if (
            install.start.getMonth() === install.end.getMonth()
            && install.start.getFullYear() === install.end.getFullYear()
        ) {
            install.cost = this.computeCost(install.start, install.end, price);

            if (install.installed) {
                install.estimatedCost = this.computeCost(install.start, this.timeModule.getEndOfMonthDay(), price);
            } else {
                delete install.estimatedCost;
            }

            const update: UpdateFilter<IUsageStatsMonthly> = { $set: install };

            if (!install.installed) {
                update.$unset = { estimatedCost: '' };
            }

            if (install._id) {
                if (minPriceUsageStats) {
                    const key = install.start.toISOString().slice(0, 7);
                    if (key !== this.currentDateKey) {
                        minPriceUsageStats[key] = (minPriceUsageStats[key] ?? 0) + install.cost;
                    }
                }
                await this.usageStatsCollection.updateOne({ _id: install._id }, update);
            } else {
                if (minPriceUsageStats) {
                    const key = install.start.toISOString().slice(0, 7);
                    if (key !== this.currentDateKey) {
                        minPriceUsageStats[key] = (minPriceUsageStats[key] ?? 0) + install.cost;
                    }
                }
                await this.usageStatsCollection.insertOne(install);
            }

            return null;
        }

        const nextMonthDate = new Date(install.start.getTime());
        nextMonthDate.setDate(1);
        nextMonthDate.setHours(0, 0, 0, 0);
        nextMonthDate.setMonth(nextMonthDate.getMonth() + 1);

        const oldInstall: IUsageStatsMonthly = {} as IUsageStatsMonthly;
        Object.assign(oldInstall, install);
        oldInstall.end = nextMonthDate;
        oldInstall.installed = false;
        oldInstall.cost = this.computeCost(oldInstall.start, oldInstall.end, price);
        delete oldInstall._id;
        delete oldInstall.estimatedCost;

        install.start = nextMonthDate;

        if (minPriceUsageStats) {
            const key = oldInstall.start.toISOString().slice(0, 7);
            if (key !== this.currentDateKey) {
                minPriceUsageStats[key] = (minPriceUsageStats[key] ?? 0) + oldInstall.cost;
            }
        }
        await this.usageStatsCollection.insertOne(oldInstall);

        return this.saveUsageStat(install, price, minPriceUsageStats);
    }

    private computeCost(
        start: Date,
        end: Date,
        price: number,
    ): number {
        const dim = this.daysInMonth(start.getUTCFullYear(), start.getMonth() + 1);
        const dailyPrice = price / dim;

        const diff = (end.getTime() - start.getTime()) / (1000 * 3600 * 24);

        return Math.round(Math.ceil(diff) * dailyPrice);
    }

    private daysInMonth(year: number, month: number): number {
        const d = new Date(Date.UTC(year, month, 0));
        return d.getUTCDate();
    }

    private async getEvents(
        coll: Collection,
        instanceId: string,
        appName: string,
        lastHighestTimestamp: Date | null,
    ): Promise<USEvent[]> {
        const filter = {
            created: {
                $lte: String(this.timeModule.getNow()),
                $gt: lastHighestTimestamp ? (lastHighestTimestamp.valueOf() + 1000).toString() : '',
            },
        };

        const res = coll.find({
            ...filter,
            iid: instanceId,
            // eslint-disable-next-line @typescript-eslint/naming-convention
            'data.aid': appName,
            type: { $nin: ['applinth_enduser_app_hearthbeat', null] },
        }).sort({ created: 1 });

        return (await res.toArray()).map((doc) => ({
            type: doc.type === 'applinth_enduser_app_install' ? USEventType.APPLINTH_END_USER_APP_INSTALL : USEventType.APPLINTH_END_USER_APP_UNINSTALL,
            instanceId: doc.iid,
            created: new Date(parseInt(doc.created, 10) / 1000),
            data: {
                appId: doc.data.aid,
                endUserId: doc.data.euid,
            },
        }));
    }

}

export interface IApplinth {
    _id: ObjectId;
    tenantId: string;
    instanceId: string;
    minPrice?: number;
    minPriceDate?: Date;
}

export interface IUsageStatsMonthly {
    appId: string;
    start: Date;
    end: Date;
    type: UsageStatsType;
    _id?: ObjectId;
    endUserId?: string;
    installId?: string;
    tenantId?: string;
    instanceId?: string;
    installed?: boolean;
    cost?: number;
    estimatedCost?: number;
}
