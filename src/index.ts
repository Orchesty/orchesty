import cors from 'cors';
import express, { Application } from 'express';
import AuthorizationMiddleware from './authorization/AuthorizationMiddleware';
import { appOptions, lokiOptions } from './config/Config';
import Mongo from './database/Mongo';
import { logger } from './logger/Logger';
import Loki from './logger/Loki';
import DocumentManager from './manager/DocumentManager';
import MetricsManager from './manager/MetricsManager';
import DefaultRouter from './router/DefaultRouter';
import DocumentRouter from './router/DocumentRouter';
import LoggerRouter from './router/LoggerRouter';
import MetricsRouter from './router/MetricsRouter';

export async function init(): Promise<IServices> {
    const loki = new Loki(lokiOptions.dsn);
    loki.start();

    const mongoClient = new Mongo();
    await mongoClient.connect();
    await mongoClient.ensureMetricsIndexes();

    const metricsManager = new MetricsManager(mongoClient);
    const documentManager = new DocumentManager(mongoClient);

    const authorizator = new AuthorizationMiddleware(mongoClient);

    const expressApp: Application = express();

    expressApp.use(

        cors({
            origin: appOptions.corsOrigin,
            optionsSuccessStatus: 200, // some legacy browsers (IE11, various SmartTVs) choke on 204
            credentials: true,
        }),
    );

    expressApp.use(authorizator.isAuthorized());

    const loggerBodyParser = express.json({ limit: appOptions.maxLoggerRequestBodyBytes });
    const defaultBodyParser = express.json({ limit: appOptions.maxRequestBodyBytes });

    expressApp.use((req, res, next) => {
        if (req.path.split('/')[1] === 'logger') {
            loggerBodyParser(req, res, next);

            return;
        }

        defaultBodyParser(req, res, next);
    });

    new DefaultRouter(expressApp, mongoClient).initRoutes();
    new LoggerRouter(expressApp, mongoClient, loki).initRoutes();
    new MetricsRouter(expressApp, metricsManager).initRoutes();
    new DocumentRouter(expressApp, documentManager).initRoutes();

    return { app: expressApp, mongo: mongoClient, loki };
}

export function listen(expressApp: Application): void {
    expressApp.disable('x-powered-by');
    expressApp.listen(appOptions.port, () => {
        logger.info(`⚡️[server]: Server is running at http://localhost:${appOptions.port}`);
    });
}

export interface IServices {
    app: Application;
    mongo: Mongo;
    loki: Loki;
}
