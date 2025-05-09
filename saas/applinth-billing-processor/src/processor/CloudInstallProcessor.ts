import { Collection, Document, ObjectId, UpdateFilter } from 'mongodb';
import Services from '../DIContainer/Services';
import { UsageStatsType } from '../enums/UsageStatsType';
import { USEvent, USEventType } from '../events';
import { container, logger } from '../index';
import Mongo, { CollectionEnum } from '../storage/mongo/Mongo';
import { BaseProcessor, countMetadata, IMetadata, IUsageStatsMonthly } from './BaseProcessor';
import IProcessor from './IProcessor';

export class CloudInstallProcessor extends BaseProcessor implements IProcessor {

    public async process(metadataRecord: Record<string, unknown>): Promise<Record<string, unknown>> {
        const mongo = container.get<Mongo>(Services.MONGO);

        const clouds = await mongo.getBillingAdminCollection(CollectionEnum.CLOUD).find().toArray();
        const colMetadata = mongo.getBillingCollection(CollectionEnum.USAGE_STATS_METADATA);

        return this.generate(
            metadataRecord,
            clouds as ICloud[],
            colMetadata,
            mongo,
        );
    }

    private async generate(
        metadataRecord: Record<string, unknown>,
        clouds: ICloud[],
        colMetadata: Collection,
        mongo: Mongo,
    ): Promise<Record<string, unknown>> {
        for (const cloud of clouds) {
            const metadata = (await colMetadata.findOne(
                { tenantId: cloud.tenantId },
            ))?.instances[cloud.instanceId];

            const lastHighestDate = metadata ? metadata.lastRunHighestEventDate : null;

            const coll = mongo.getUsageStatsCollection(CollectionEnum.EVENTS);
            const res = await coll.find({ iid: cloud.instanceId }).sort({ created: 1 }).limit(1).toArray();

            const historyStart = res.length ? new Date(res[0].created / 1000) : null;
            const highestDate = await this.generateMonthlyStats(coll, lastHighestDate, cloud);

            countMetadata(
                metadataRecord as Record<string, IMetadata>,
                cloud.instanceId,
                cloud.tenantId,
                historyStart,
                highestDate,
            );
        }

        return metadataRecord;
    }

    private async generateMonthlyStats(
        eventsCollection: Collection,
        lastHighestDate: Date | null,
        cloud: ICloud,
    ): Promise<Date | null> {
        const currentDate = new Date(this.timeModule.getNow());
        let highestDate = lastHighestDate ? new Date(lastHighestDate) : null;

        if (lastHighestDate && currentDate < lastHighestDate) {
            throw new Error('Current date is lower than last run date!');
        }

        const installed: IUsageStatsMonthly[] = await this.usageStatsCollection.find({
            installed: true,
            instanceId: cloud.instanceId,
            end: { $lt: currentDate },
            type: UsageStatsType.CLOUD_INSTALL,
        }).sort({ end: 1 }).toArray() as IUsageStatsMonthly[];

        const events = await this.getEvents(
            eventsCollection,
            lastHighestDate,
            ['cloud_install', 'cloud_uninstall'],
            cloud.instanceId,
        );
        logger.info(`Found ${events.length} new Cloud install events for instanceId ${cloud.instanceId}`);

        if (events.length) {
            const newestEvent = events[events.length - 1];

            if (!highestDate || highestDate < newestEvent.created) {
                highestDate = newestEvent.created;
            }
        }

        for (const event of events) {
            const install = installed[installed.length - 1];

            if (install) {
                if (event.type === USEventType.CLOUD_INSTALL) {
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
                                cloud.instanceId,
                                cloud.tenantId,
                                true,
                                UsageStatsType.CLOUD_INSTALL,
                            ));
                        }
                    }
                } else {
                    install.end = event.created;
                    install.installed = false;
                }
            } else if (event.type === USEventType.CLOUD_INSTALL) {
                installed.push(this.generateUsageStat(
                    event,
                    currentDate,
                    cloud.instanceId,
                    cloud.tenantId,
                    true,
                    UsageStatsType.CLOUD_INSTALL,
                ));
            }
        }

        for (const install of installed) {
            if (install.installed) {
                install.end = currentDate;
            }

            await this.saveUsageStat(install, cloud.price);
        }

        return highestDate;
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

        return {
            start,
            end,
            type,
            instanceId,
            tenantId,
            installed,
        };
    }

    private async saveUsageStat(
        install: IUsageStatsMonthly,
        price: number,
    ): Promise<IUsageStatsMonthly | null> {
        if (
            install.start.getMonth() === install.end.getMonth()
            && install.start.getFullYear() === install.end.getFullYear()
        ) {
            install.cost = price;

            const update: UpdateFilter<Document> = { $set: install };

            if (install._id) {
                await this.usageStatsCollection.updateOne({ _id: install._id }, update);
            } else {
                await this.usageStatsCollection.insertOne(install);
            }

            return null;
        }

        const nextMonthDate = this.prepareNextMonthDate(install);
        const oldInstall = this.cloneOldInstall(install, nextMonthDate, price);
        install.start = nextMonthDate;

        await this.usageStatsCollection.insertOne(oldInstall);

        return this.saveUsageStat(install, price);
    }

}

export interface ICloud {
    _id: ObjectId;
    tenantId: string;
    instanceId: string;
    plan: string;
    price: number;
    period: string;
    startDate: Date;
    closeDate?: Date | null;
}
