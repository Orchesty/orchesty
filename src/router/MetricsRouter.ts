import { Application } from 'express';
import Joi, { ValidationResult } from 'joi';
import MetricsManager, { IConnectorFields, IMetricsInput, IMonolithFields } from '../manager/MetricsManager';

const monolithInputSchema = Joi.object<IMetricsInput<IMonolithFields>>({
    fields: Joi.object({
        created: Joi.string().isoDate().required(),
        fpm_request_total_duration: Joi.number().required(),
        fpm_cpu_user_time: Joi.number().required(),
        fpm_cpu_kernel_time: Joi.number().required(),
    }).required(),
    tags: Joi.object({
        topology_id: Joi.string(),
        node_id: Joi.string(),
        correlation_id: Joi.string(),
    }).required(),
}).required();

const connectorsInputSchema = Joi.object<IMetricsInput<IConnectorFields>>({
    /* eslint-disable @typescript-eslint/naming-convention */
    fields: Joi.object({
        created: Joi.string().isoDate().required(),
        user_id: Joi.string().required(),
        application_id: Joi.string().required(),
        send_request_total_duration: Joi.number().required(),
        response_code: Joi.number().required(),
    }).required(),
    /* eslint-enable @typescript-eslint/naming-convention */
    tags: Joi.object({
        topology_id: Joi.string(),
        node_id: Joi.string(),
        correlation_id: Joi.string(),
    }).required(),
}).required();

export default class MetricsRouter {

    public constructor(private readonly app: Application, private readonly metricsManager: MetricsManager) {

    }

    public initRoutes(): void {
        // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.post('/metrics/:measurement', async (req, res) => {
            const { measurement } = req.params;

            let result = {} as ValidationResult;
            switch (measurement) {
                case 'monolith':
                    result = monolithInputSchema.validate(req.body);
                    break;
                case 'connectors':
                    result = connectorsInputSchema.validate(req.body);
                    break;
                default:
                    throw new Error('Unsupported metric measurement!');
            }

            if (result.error) {
                res.statusCode = 400;
                res.json({ message: { error: result.error.message } });
                return;
            }

            try {
                await this.metricsManager.saveMetrics(req.body, measurement);
                res.json({ message: { status: 'OK', data: '' } });
            } catch (e) {
                if (e instanceof Error) {
                    res.json({ message: { error: e.message } });
                }
                res.json({ message: 'Worker-api: mongo unknown error' });
            }
        });
    }

}
