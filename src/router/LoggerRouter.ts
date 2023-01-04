import axios from 'axios';
import { Application } from 'express';
import Joi from 'joi';
import { fluentdOptions } from '../config/Config';
import ResultCode from '../enum/ResultCode';

const inputSchema = Joi.object<ILogInput>({
    node_id: Joi.string(),
    user_id: Joi.string(),
    node_name: Joi.string(),
    topology_id: Joi.string(),
    topology_name: Joi.string(),
    correlation_id: Joi.string(),
    result_code: Joi.number().valid(...Object.values(ResultCode)),
    result_message: Joi.string(),
    stacktrace: Joi.object({
        trace: Joi.string(),
        message: Joi.string().required(),
    }),
    data: Joi.string(),
    isForUi: Joi.boolean(),
    timestamp: Joi.number().strict(true).required(),
    hostname: Joi.string().required(),
    type: Joi.string().required(),
    severity: Joi.string().required(),
    message: Joi.string().required(),
}).required();

export default class LoggerRouter {

    public constructor(private readonly app: Application) {
    }

    public initRoutes(): void {
    // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.post('/logger/logs', async (req, res) => {
            const result = inputSchema.validate(req.body);
            if (result.error) {
                res.statusCode = 400;
                res.json({ message: { error: result.error.message } });
                return;
            }
            try {
                const fluentdResult = await axios.post(`http://${fluentdOptions.fluentdDsn}/orchesty`, req.body);
                res.json({ message: { status: fluentdResult.statusText, data: fluentdResult.data } });
            } catch (e) {
                if (e instanceof Error) {
                    res.json({ message: { error: e.message } });
                }
                res.json({ message: 'Worker-api: Fluentd unknown error' });
            }
        });
    }

}

interface ILogInput {
    timestamp: number;
    hostname: string;
    type: string;
    severity: string;
    message: string;
    node_id?: string;
    user_id?: string;
    node_name?: string;
    topology_id?: string;
    topology_name?: string;
    correlation_id?: string;
    result_code?: ResultCode;
    result_message?: string;
    stacktrace?: {
        message: string;
        trace?: string;
    };
    data?: string;
    isForUi?: boolean;
}
