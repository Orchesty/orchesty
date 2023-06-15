import Services from '../DIContainer/Services';
import { container } from '../index';
import Mongo, { CollectionEnum } from '../storage/mongo/Mongo';
import TimeModule from '../TimeModule';
import IProcessor from './IProcessor';

export class ProcessorManager {

    private registeredProcessors: IProcessor[] = [];

    public registerProcessor(processor: IProcessor | IProcessor[]): void {
        if (Array.isArray(processor)) {
            this.registeredProcessors = this.registeredProcessors.concat(processor);
        } else {
            this.registeredProcessors.push(processor);
        }
    }

    public async process(): Promise<void> {
        const metadataRecord: Record<string, unknown> = {};
        const mongo = container.get<Mongo>(Services.MONGO);
        const timeModule = container.get<TimeModule>(Services.TIME_MODULE);
        const colMetadata = mongo.getBillingCollection(CollectionEnum.USAGE_STATS_METADATA);

        for (const processor of this.registeredProcessors) {
            await processor.process(metadataRecord);
        }

        for (const key of Object.keys(metadataRecord)) {
            const value = metadataRecord[key] as {
                billingHistoryStart: string;
                lastRunHighestEventDate: string;
                tenantId: string;
            };

            await colMetadata.updateOne({ tenantId: value.tenantId }, {
                $set: {
                    tenantId: value.tenantId,
                    [`instances.${key}.billingHistoryStart`]: value.billingHistoryStart,
                    [`instances.${key}.billingHistoryEnd`]: new Date(timeModule.getNow()),
                    [`instances.${key}.lastRunHighestEventDate`]: value.lastRunHighestEventDate,
                },
            }, { upsert: true });
        }
    }

}
