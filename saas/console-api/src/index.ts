import cors from 'cors';
import express, { Express, RequestHandler } from 'express';
import { initializeApp } from 'firebase/app';
import admin from 'firebase-admin';
import * as fs from 'fs';
import jsyaml from 'js-yaml';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-expect-error
import { configure, initializeMiddleware } from 'oas-tools';
import { app, firebase, mongo } from './config/config';
import DIContainer from './DIContainer/Container';
import Services from './DIContainer/Services';
import { logger } from './logger/logger';
import ClientService from './services/ClientService';
import SupportService from './services/SupportService';
import TenantService from './services/TenantService';
import UsageStatsService from './services/UsageStatsService';
import UsersService from './services/UsersService';
import Mongo from './storage/mongo/Mongo';

const container = new DIContainer();

let fbAdminConfig = {};
const fbAdminPrivKey = `${__dirname}/../privateKey.json`;
if (fs.existsSync(fbAdminPrivKey)) {
    fbAdminConfig = {
        credential: app.env === 'prod' ? admin.credential.applicationDefault() : admin.credential.cert(fbAdminPrivKey),
    };
}

const authApp = admin.initializeApp(fbAdminConfig);

const fbApp = initializeApp({
    apiKey: firebase.apiKey,
    authDomain: firebase.authDomain,
});

async function initServices(): Promise<void> {
    const db = new Mongo(mongo.dsn);
    await db.connect();
    await db.createBillingIndexes();
    await db.createCloudIndexes();
    logger.info('Database connected');
    const usageStatsService = new UsageStatsService(db);
    const usersService = new UsersService();
    const clientService = new ClientService(db);
    const supportService = new SupportService(db);
    const tenantService = new TenantService(db);
    container.set(Services.STORAGE, db);
    container.set(Services.USAGE_STATS_SERVICE, usageStatsService);
    container.set(Services.USERS_SERVICE, usersService);
    container.set(Services.CLIENTS_SERVICE, clientService);
    container.set(Services.SUPPORTS_SERVICE, supportService);
    container.set(Services.TENANT_SERVICE, tenantService);
}

function createServer(): Express {
    const server = express();
    server.use(
        // eslint-disable-next-line @typescript-eslint/no-unsafe-call
        cors({
            origin: app.corsOrigin,
            optionsSuccessStatus: 200, // some legacy browsers (IE11, various SmartTVs) choke on 204
            credentials: true,
        }),
    );

    const spec = fs.readFileSync(app.openapiPath, 'utf8');
    const oasDoc = jsyaml.load(spec);
    const options = {
        controllers: `${__dirname}/controllers`,
        loglevel: 'warn',
    };
    // eslint-disable-next-line @typescript-eslint/no-unsafe-call
    configure(options);

    // eslint-disable-next-line @typescript-eslint/no-unsafe-call
    initializeMiddleware(oasDoc, server, (middleware: { swaggerRouter: RequestHandler }) => {
        server.use((req, res, next) => {
            next(res.status(404).send({ msg: 'Page not found!' }));
        });
        server.use(middleware.swaggerRouter);
    });

    container.set(Services.SERVER, server);
    return server;
}

export { authApp, container, createServer, fbApp, initServices };
