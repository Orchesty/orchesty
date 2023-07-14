import { Collection, ObjectId } from 'mongodb';
import Services from '../DIContainer/Services';
import { UsageStatsType } from '../enums/UsageStatsType';
import { getCode, USEvent, USEventData } from '../events';
import { container } from '../index';
import Mongo, { CollectionEnum } from '../storage/mongo/Mongo';
import TimeModule from '../TimeModule';

export function countMetadata(
    metadataRecord: Record<string, IMetadata>,
    instanceId: string,
    tenantId: string,
    historyStart: Date | null,
    highestDate: Date | null,
): Record<string, IMetadata> {
    if (metadataRecord[instanceId]) {
        metadataRecord[instanceId].tenantId = tenantId;

        if (historyStart) {
            if ((metadataRecord[instanceId].billingHistoryStart || new Date()) > historyStart) {
                metadataRecord[instanceId].billingHistoryStart = historyStart;
            }
        }

        if (highestDate) {
            if ((metadataRecord[instanceId].lastRunHighestEventDate || new Date(1)) < highestDate) {
                metadataRecord[instanceId].lastRunHighestEventDate = highestDate;
            }
        }

        return metadataRecord;
    }

    metadataRecord[instanceId] = {
        billingHistoryStart: historyStart,
        lastRunHighestEventDate: highestDate,
        tenantId,
    };

    return metadataRecord;
}

export class BaseProcessor {

    protected readonly timeModule: TimeModule;

    protected readonly usageStatsCollection: Collection;

    protected readonly moduleCollection: Collection;

    public constructor() {
        this.timeModule = container.get<TimeModule>(Services.TIME_MODULE);

        const mongo = container.get<Mongo>(Services.MONGO);
        this.usageStatsCollection = mongo.getBillingCollection(CollectionEnum.USAGE_STATS_MONTHLY);
        this.moduleCollection = mongo.getBillingAdminCollection(CollectionEnum.MODULE);
    }

    protected async getEvents(
        coll: Collection,
        lastHighestTimestamp: Date | null,
        types: string[],
        instanceId?: string,
        appName?: string,
    ): Promise<USEvent[]> {
        // eslint-disable-next-line @typescript-eslint/naming-convention
        const filter: { 'data.aid'?: string; created: unknown } = {
            created: {
                $lte: String(this.timeModule.getNow()),
                $gt: lastHighestTimestamp ? (lastHighestTimestamp.valueOf() + 1000).toString() : '',
            },
        };

        if (appName) {
            filter['data.aid'] = appName;
        }

        const res = coll.find({
            ...filter,
            iid: instanceId,
            type: { $in: types },
        }).sort({ created: 1 });

        return (await res.toArray()).map((doc) => {
            const data: USEventData = {};
            if (doc.data?.aid) {
                data.appId = doc.data.aid;
            }
            if (doc.data?.euid) {
                data.endUserId = doc.data.euid;
            }
            if (doc.data?.total) {
                data.total = doc.data.total;
            }
            if (doc.data?.day) {
                data.day = doc.data.day;
            }

            return {
                type: getCode(doc.type),
                instanceId: doc.iid,
                created: new Date(parseInt(doc.created, 10) / 1000),
                data,
            };
        });
    }

    protected prepareNextMonthDate(install: IUsageStatsMonthly): Date {
        const nextMonthDate = new Date(install.start.getTime());
        nextMonthDate.setDate(1);
        nextMonthDate.setHours(0, 0, 0, 0);
        nextMonthDate.setMonth(nextMonthDate.getMonth() + 1);

        return nextMonthDate;
    }

    protected cloneOldInstall(install: IUsageStatsMonthly, nextMonthDate: Date, price: number): IUsageStatsMonthly {
        const oldInstall: IUsageStatsMonthly = {} as IUsageStatsMonthly;

        Object.assign(oldInstall, install);
        oldInstall.end = nextMonthDate;
        oldInstall.installed = false;
        oldInstall.cost = [
            'enduser_app_install',
            'enduser_app_uninstall',
        ].includes(oldInstall.type) ? this.computeCost(oldInstall.start, oldInstall.end, price) : price;
        delete oldInstall._id;
        delete oldInstall.estimatedCost;

        return oldInstall;
    }

    protected computeCost(
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

}

export interface IUsageStatsMonthly {
    start: Date;
    end: Date;
    type: UsageStatsType;
    _id?: ObjectId;
    appId?: string | null;
    endUserId?: string;
    installId?: string;
    tenantId?: string;
    instanceId?: string;
    installed?: boolean;
    cost?: number;
    estimatedCost?: number;
}

export interface IMetadata {
    billingHistoryStart: Date | null;
    lastRunHighestEventDate: Date | null;
    tenantId: string;
    billingHistoryEnd?: Date | null;
}
