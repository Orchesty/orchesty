import cors from 'cors';
import express, { Application } from 'express';
import AuthorizationMiddleware from './authorization/AuthorizationMiddleware';
import { appOptions } from './config/Config';
import Mongo from './database/Mongo';
import { logger } from './logger/Logger';
import DefaultRouter from './router/DefaultRouter';

export async function init(): Promise<Application> {
    const mongoClient = new Mongo();
    await mongoClient.connect();

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

    return expressApp;
}

export function listen(expressApp: Application): void {
    expressApp.disable('x-powered-by');
    expressApp.listen(appOptions.port, () => {
        logger.info(`⚡️[server]: Server is running at http://localhost:${appOptions.port}`);
    });
}
