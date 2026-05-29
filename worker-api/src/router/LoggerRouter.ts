import { Application } from 'express';
import Joi from 'joi';
import { mongoOptions } from '../config/Config';
import Mongo from '../database/Mongo';
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

    public constructor(private readonly app: Application, private readonly mongo: Mongo) {
    }

    public initRoutes(): void {
        this.app.post('/logger/logs', async (req, res) => {
            const result = inputSchema.validate(req.body, { allowUnknown: true });
            if (result.error) {
                logger.error(result.error);
                res.statusCode = 400;
                res.json({ message: { error: result.error.message } });
                return;
            }
            try {
                if (req.body.isForUi !== true) {
                    res.json({ message: { status: 'OK', data: '' } });
                    return;
                }

                const record = this.createUiLogRecord(req.body as ILogInput);
                await this.mongo.getCollection(mongoOptions.logsCollection).insertOne(record);

                const resp = { message: { status: 'OK', data: '' } };
                logger.debug(resp);
                res.json(resp);
            } catch (e) {
                res.statusCode = 400;
                logger.error(e);
                if (e instanceof Error) {
                    res.json({ message: { error: e.message } });
                    return;
                }
                res.json({ message: 'Worker-api: Logs unknown error' });
            }
        });

        this.app.post('/logger/loki', (req, res) => {
            const result = inputSchema.validate(req.body, { allowUnknown: true });

            if (result.error) {
                logger.error(result.error);
                res.statusCode = 400;
                res.json({ message: { error: result.error.message } });
                return;
            }

            logger.info(req.body);
            res.json({ message: { status: 'OK' } });
        });
    }

    private createUiLogRecord(log: ILogInput): IUiLogRecord {
        const timestamp = Math.floor(new Date(log.timestamp).getTime() / 1000) | 0;

        return {
            message: log.message,
            pipes: {
                user_id: log.userId,
                parent_id: log.parentId,
                severity: log.level ?? log.levelName,
                service: log.service,
                timestamp,
                node_id: log.nodeId,
                topology_id: log.topologyId,
                process_id: log.processId,
                correlation_id: log.correlationId,
                applications: log.applications,
                previousCorrelationId: log.previousCorrelationId,
                sequenceId: log.sequenceId,
                previousNodeId: log.previousNodeId,
            },
            ts: new Date(),
        };
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
    level?: string;
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

interface IUiLogRecord {
    message: string;
    pipes: {
        user_id?: string;
        parent_id?: string;
        severity?: string;
        service: string;
        timestamp: number;
        node_id?: string;
        topology_id?: string;
        process_id?: string;
        correlation_id?: string;
        applications?: string;
        previousCorrelationId?: string;
        sequenceId?: string;
        previousNodeId?: string;
    };
    ts: Date;
}
