import axios from 'axios';
import { Application } from 'express';
import Joi from 'joi';
import { fluentdOptions } from '../config/Config';
import ResultCode from '../enum/ResultCode';

const inputSchema = Joi.object<ILogInput>({
    nodeId: Joi.string(),
    userId: Joi.string(),
    nodeName: Joi.string(),
    topologyId: Joi.string(),
    topologyName: Joi.string(),
    correlationId: Joi.string(),
    resultCode: Joi.number().valid(...Object.values(ResultCode)),
    resultMessage: Joi.string(),
    stacktrace: Joi.object({
        trace: Joi.string(),
        message: Joi.string().required(),
    }),
    data: Joi.string(),
    isForUi: Joi.boolean(),
    timestamp: Joi.number().strict(true).required(),
    hostname: Joi.string().required(),
    service: Joi.string().required(),
    level: Joi.string().required(),
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
                    return;
                }
                res.json({ message: 'Worker-api: Fluentd unknown error' });
            }
        });
    }

}

interface ILogInput {
    timestamp: number;
    hostname: string;
    service: string;
    level: string;
    message: string;
    nodeId?: string;
    userId?: string;
    nodeName?: string;
    topologyId?: string;
    topologyName?: string;
    correlationId?: string;
    resultCode?: ResultCode;
    resultMessage?: string;
    stacktrace?: {
        message: string;
        trace?: string;
    };
    data?: string;
    isForUi?: boolean;
}
