import { Collection, ObjectId, UpdateFilter } from 'mongodb';
import Services from '../DIContainer/Services';
import { UsageStatsType } from '../enums/UsageStatsType';
import { USEvent, USEventType } from '../events';
import { container, logger } from '../index';
import Mongo, { CollectionEnum } from '../storage/mongo/Mongo';
import { BaseProcessor, countMetadata, IMetadata, IUsageStatsMonthly } from './BaseProcessor';
import IProcessor from './IProcessor';

export class EndUserAppInstallProcessor extends BaseProcessor implements IProcessor {

    public async process(metadataRecord: Record<string, unknown>): Promise<Record<string, unknown>> {
        const mongo = container.get<Mongo>(Services.MONGO);

        const applinths = await mongo.getBillingAdminCollection(CollectionEnum.APPLINTH).find().toArray();
        const colMetadata = mongo.getBillingCollection(CollectionEnum.USAGE_STATS_METADATA);
        const colModule = mongo.getBillingAdminCollection(CollectionEnum.MODULE);

        return this.generate(
            metadataRecord,
            applinths as IApplinth[],
            colMetadata,
            colModule,
            mongo,
        );
    }

    private async generate(
        metadataRecord: Record<string, unknown>,
        applinths: IApplinth[],
        colMetadata: Collection,
        colModule: Collection,
        mongo: Mongo,
    ): Promise<Record<string, unknown>> {
        for (const applinth of applinths) {
            const metadata = (await colMetadata.findOne(
                { tenantId: applinth.tenantId },
            ))?.instances[applinth.instanceId];

            const lastHighestDate = metadata ? metadata.lastRunHighestEventDate : null;

            const coll = mongo.getUsageStatsCollection(CollectionEnum.EVENTS);
            const res = await coll.find({ iid: applinth.instanceId }).sort({ created: 1 }).limit(1).toArray();

            const historyStart = res.length ? new Date(res[0].created / 1000) : null;
            const highestDate = await this.generateMonthlyStats(colModule, coll, applinth, lastHighestDate);

            countMetadata(
                metadataRecord as Record<string, IMetadata>,
                applinth.instanceId,
                applinth.tenantId,
                historyStart,
                highestDate,
            );
        }

        return metadataRecord;
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

        if (lastHighestDate && currentDate < lastHighestDate) {
            throw new Error('Current date is lower than last run date!');
        }

        let highestDate = lastHighestDate ? new Date(lastHighestDate) : null;

        for (const module of await moduleCollection.find({ applinthId }).toArray()) {
            const minPrice = module.minPrice ?? applinth.minPrice;
            const minPriceDate = module.minPriceDate ?? applinth.minPriceDate;

            const installed: IUsageStatsMonthly[] = await this.usageStatsCollection.find({
                installed: true,
                instanceId,
                end: { $lt: currentDate },
                appId: module.appName,
                type: UsageStatsType.ENDUSER_APP_INSTALL,
            }).sort({ end: 1 }).toArray() as IUsageStatsMonthly[];

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

        const events = await this.getEvents(
            eventsCollection,
            lastHighestDate,
            ['applinth_enduser_app_install', 'applinth_enduser_app_uninstall'],
            applinth.instanceId,
            appName,
        );
        logger.info(`Found ${events.length} new EndUser Application install events for instanceId ${applinth.instanceId} and appName ${appName}`);

        if (events.length) {
            const newestEvent = events[events.length - 1];

            if (!highestDate || highestDate < newestEvent.created) {
                highestDate = newestEvent.created;
            }
        }

        for (const event of events) {
            const install = installed
                .findLast((i) => i.endUserId === event?.data?.endUserId && i.appId === event?.data?.appId);

            if (install) {
                if (event.type === USEventType.APPLINTH_END_USER_APP_INSTALL) {
                    if (install.installed === false) {
                        const iEnd: Date = new Date(install.end);
                        const eCreate = new Date(event.created);

                        if (iEnd.setHours(0, 0, 0, 0) === eCreate.setHours(0, 0, 0, 0)) {
                            install.end = currentDate;
                            install.installed = true;
                        } else {
                            installed.push(this.generateUsageStat(
                                event,
                                currentDate,
                                instanceId,
                                tenantId,
                                true,
                                UsageStatsType.ENDUSER_APP_INSTALL,
                            ));
                        }
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
                    UsageStatsType.ENDUSER_APP_INSTALL,
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
        const { created: start } = event;
        const endUserId = event.data?.endUserId;

        return {
            appId: event.data?.appId ?? '',
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

        const nextMonthDate = this.prepareNextMonthDate(install);
        const oldInstall = this.cloneOldInstall(install, nextMonthDate, price);
        install.start = nextMonthDate;

        if (minPriceUsageStats) {
            const key = oldInstall.start.toISOString().slice(0, 7);
            if (key !== this.currentDateKey) {
                minPriceUsageStats[key] = (minPriceUsageStats[key] ?? 0) + (oldInstall?.cost ?? 0);
            }
        }
        await this.usageStatsCollection.insertOne(oldInstall);

        return this.saveUsageStat(install, price, minPriceUsageStats);
    }

}

export interface IApplinth {
    _id: ObjectId;
    tenantId: string;
    instanceId: string;
    minPrice?: number;
    minPriceDate?: Date;
}
