import { Application } from 'express';
import Joi from 'joi';
import MetricsManager, { IMetricsInput } from '../manager/MetricsManager';

const inputSchema = Joi.object<IMetricsInput>({
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

export default class MetricsRouter {

    public constructor(private readonly app: Application, private readonly metricsManager: MetricsManager) {

    }

    public initRoutes(): void {
    // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.post('/metrics/:measurement', async (req, res) => {
            const { measurement } = req.params;

            const result = inputSchema.validate(req.body);

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
