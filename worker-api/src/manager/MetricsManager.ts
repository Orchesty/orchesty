import Mongo from '../database/Mongo';

export default class MetricsManager {

    public constructor(private readonly mongo: Mongo) {
    }

    public async saveMetrics(metrics: IMetricsInput, measurement: string): Promise<void> {
        const { fields, tags } = metrics;

        const measurementCollection = this.mongo.getMeasurementCollection(measurement);
        await measurementCollection.insertOne({
            tags,
            fields: {
                created: new Date(fields.created),
            },
        } as IMetricsOutput);
    }

}

export interface IMetricsInput {
    fields: IInputFields;
    tags: ITags;
}

export interface IMetricsOutput {
    fields: IOutputFields;
    tags: ITags;
}

interface IInputFields extends IBaseFields {
    created: string;
}

interface IOutputFields extends IBaseFields {
    created: Date;

}

interface IBaseFields {
    fpm_request_total_duration: number;
    fpm_cpu_user_time: number;
    fpm_cpu_kernel_time: number;
}

interface ITags {
    topology_id?: string;
    node_id?: string;
    correlation_id?: string;
}
