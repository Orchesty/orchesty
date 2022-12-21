import { NextFunction, Request, Response } from 'express';
import Mongo from '../database/Mongo';
import { ScopeEnum } from './ScopeEnum';

export const ORCHESTY_API_KEY = 'orchesty-api-key';

export default class AuthorizationMiddleware {

    public constructor(protected readonly mongoClient: Mongo) {
    }

    public isAuthorized(): (req: Request, res: Response, next: NextFunction) => Promise<void> {
        const { mongoClient } = this;

        return async function(req: Request, res: Response, next: NextFunction) {
            let scopes: string[] = [];

            switch (req.path) {
                case '/':
                case '/status':
                    next();
                    return;
                case '/logger':
                    scopes = [ScopeEnum.LOG_WRITE];
                    break;
                case '/metrics':
                    scopes = [ScopeEnum.METRICS_WRITE];
                    break;
                case '/document':
                    scopes = [ScopeEnum.WORKER_ALL];
                    break;
                default:
                    res.statusCode = 401;
                    res.json({ message: 'Bad scopes' });
                    return;
            }

            const apiKeyHeader = req.header(ORCHESTY_API_KEY);
            if (!apiKeyHeader) {
                res.statusCode = 401;
                res.json({ message: `Header ${ORCHESTY_API_KEY} is missing` });
                return;
            }

            const apiKeyRepo = mongoClient.getApiKeyCollection();
            const apiKeyMongo = await apiKeyRepo.findOne({ key: apiKeyHeader, scopes: { $all: scopes } });

            if (!apiKeyMongo) {
                res.statusCode = 401;
                res.json({ message: 'Bad credentials' });
                return;
            }

            next();
        };
    }

}
