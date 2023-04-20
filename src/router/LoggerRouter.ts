import axios from 'axios';
import { Application } from 'express';
import Joi from 'joi';
import { fluentdOptions } from '../config/Config';
import ResultCode from '../enum/ResultCode';
import { logger } from '../logger/Logger';

const inputSchema = Joi.object<ILogInput>({
    message: Joi.string().required(),
    service: Joi.string().required(),
    timestamp: Joi.number().strict(true).required(),
    applications: Joi.string(),
    correlationId: Joi.string(),
    data: Joi.string(),
    isForUi: Joi.boolean(),
    levelName: Joi.string(),
    nodeId: Joi.string(),
    nodeName: Joi.string(),
    parentId: Joi.string(),
    previousCorrelationId: Joi.string(),
    previousNodeId: Joi.string(),
    processId: Joi.string(),
    resultCode: Joi.number().valid(...Object.values(ResultCode)),
    resultMessage: Joi.string(),
    sequenceId: Joi.string(),
    stacktrace: Joi.object({ message: Joi.string().required(), trace: Joi.string() }),
    topologyId: Joi.string(),
    topologyName: Joi.string(),
    userId: Joi.string(),
}).required();

export default class LoggerRouter {

    public constructor(private readonly app: Application) {
    }

    public initRoutes(): void {
    // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.post('/logger/logs', async (req, res) => {
            const result = inputSchema.validate(req.body, { allowUnknown: true });
            if (result.error) {
                logger.error(result.error);
                res.statusCode = 400;
                res.json({ message: { error: result.error.message } });
                return;
            }
            try {
                req.body.timestamp = Math.floor(new Date(req.body.timestamp).getTime() / 1000) | 0;

                const fluentdResult = await axios.post(`http://${fluentdOptions.fluentdDsn}/orchesty`, req.body);
                const resp = { message: { status: fluentdResult.statusText, data: fluentdResult.data } };
                logger.debug(resp);
                res.json(resp);
            } catch (e) {
                res.statusCode = 400;
                logger.error(e);
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
    message: string;
    service: string;
    timestamp: number;
    applications?: string;
    correlationId?: string;
    data?: string;
    isForUi?: boolean;
    levelName?: string;
    nodeId?: string;
    nodeName?: string;
    parentId?: string;
    previousCorrelationId?: string;
    previousNodeId?: string;
    processId?: string;
    resultCode?: ResultCode;
    resultMessage?: string;
    sequenceId?: string;
    stacktrace?: { message: string; trace?: string };
    topologyId?: string;
    topologyName?: string;
    userId?: string;
}
