import Mongo from '../database/Mongo';

export default class MetricsManager {

    public constructor(private readonly mongo: Mongo) {
    }

    public async saveMetrics(metrics: IMetricsInput<{ created: Date }>, measurement: string): Promise<void> {
        const { fields, tags } = metrics;

        const measurementCollection = this.mongo.getMetricsCollection(measurement);
        fields.created = new Date(fields.created);
        await measurementCollection.insertOne({
            tags,
            fields,
        });
    }

}

export interface IMetricsInput<T extends { created: Date }> {
    fields: T;
    tags: ITags;
}

export interface IMonolithFields {
    created: Date;
    fpm_request_total_duration: number;
    fpm_cpu_user_time: number;
    fpm_cpu_kernel_time: number;
}

/* eslint-disable @typescript-eslint/naming-convention */
export interface IConnectorFields {
    created: Date;
    user_id: string;
    application_id: string;
    send_request_total_duration: number;
    response_code?: number;
}

/* eslint-enable @typescript-eslint/naming-convention */

interface ITags {
    topology_id?: string;
    node_id?: string;
    correlation_id?: string;
}
