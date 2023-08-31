import cors from 'cors';
import express, { Application } from 'express';
import AuthorizationMiddleware from './authorization/AuthorizationMiddleware';
import { appOptions } from './config/Config';
import Mongo from './database/Mongo';
import { logger } from './logger/Logger';
import DocumentManager from './manager/DocumentManager';
import MetricsManager from './manager/MetricsManager';
import DefaultRouter from './router/DefaultRouter';
import DocumentRouter from './router/DocumentRouter';
import LoggerRouter from './router/LoggerRouter';
import MetricsRouter from './router/MetricsRouter';

export async function init(): Promise<IServices> {
    const mongoClient = new Mongo();
    await mongoClient.connect();

    const metricsManager = new MetricsManager(mongoClient);
    const documentManager = new DocumentManager(mongoClient);

    const authorizator = new AuthorizationMiddleware(mongoClient);

    const expressApp: Application = express();
    expressApp.use(express.json());

    expressApp.use(
        // eslint-disable-next-line @typescript-eslint/no-unsafe-call
        cors({
            origin: appOptions.corsOrigin,
            optionsSuccessStatus: 200, // some legacy browsers (IE11, various SmartTVs) choke on 204
            credentials: true,
        }),
    );

    // eslint-disable-next-line @typescript-eslint/no-misused-promises
    expressApp.use(authorizator.isAuthorized());

    new DefaultRouter(expressApp, mongoClient).initRoutes();
    new LoggerRouter(expressApp).initRoutes();
    new MetricsRouter(expressApp, metricsManager).initRoutes();
    new DocumentRouter(expressApp, documentManager).initRoutes();

    return { app: expressApp, mongo: mongoClient };
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
}
