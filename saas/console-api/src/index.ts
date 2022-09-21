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
import initializeLogger from './logger/logger';
import Mongo from './storage/mongo/Mongo';
import TenantService from './tenants/TenantService';
import UsageStatsService from './usageStats/UsageStatsService';
import UsersService from './users/UsersService';

/* eslint-disable import/no-mutable-exports */
let db: Mongo;
let usageStatsService: UsageStatsService;
let usersService: UsersService;
let tenantService: TenantService;
let server: Express;
const logger = initializeLogger(app.debug);
/* eslint-enable import/no-mutable-exports */

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
});

async function initServices(): Promise<void> {
    db = new Mongo(mongo.dsn);
    await db.connect();
    await db.createBillingIndexes();
    await db.createCloudIndexes();
    logger.info('Database connected');
    usageStatsService = new UsageStatsService(db);
    usersService = new UsersService();
    tenantService = new TenantService(db);
}

function createServer(): Express {
    server = express();
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
        server.use(middleware.swaggerRouter);
    });

    return server;
}

export {
    authApp, createServer, db, fbApp,
    initServices, logger, server, tenantService, usageStatsService, usersService };
