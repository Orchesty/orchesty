import cors from 'cors';
import express, { Express, RequestHandler } from 'express';
import { initializeApp } from 'firebase/app';
import admin from 'firebase-admin';
import * as fs from 'fs';
import jsyaml from 'js-yaml';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-expect-error
import { configure, initializeMiddleware } from 'oas-tools';
import CloudController from './admin/cloudController/CloudController';
import AddressService from './admin/services/AddressService';
import ApplinthService from './admin/services/ApplinthService';
import ClientService from './admin/services/ClientService';
import CloudService from './admin/services/CloudService';
import CorrectionService from './admin/services/CorrectionService';
import ModuleService from './admin/services/ModuleService';
import OrchestyService from './admin/services/OrchestyService';
import { app, firebase, mongo } from './base/config/config';
import DIContainer from './base/DIContainer/Container';
import Services from './base/DIContainer/Services';
import { logger } from './base/logger/logger';
import TenantService from './base/services/TenantService';
import UsersService from './base/services/UsersService';
import Mongo from './base/storage/mongo/Mongo';
import UsageStatsService from './billing/services/UsageStatsService';

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
    const addressService = new AddressService(db);
    const cloudService = new CloudService(db);
    const applinthService = new ApplinthService(db, cloudService, new CloudController());
    const orchestyService = new OrchestyService(db, cloudService, new CloudController());
    const correctionService = new CorrectionService(db);
    const moduleService = new ModuleService(db);
    const tenantService = new TenantService(db);
    container.set(Services.STORAGE, db);
    container.set(Services.USAGE_STATS_SERVICE, usageStatsService);
    container.set(Services.USERS_SERVICE, usersService);
    container.set(Services.CLIENTS_SERVICE, clientService);
    container.set(Services.ADDRESS_SERVICE, addressService);
    container.set(Services.CLOUD_SERVICE, cloudService);
    container.set(Services.APPLINTH_SERVICE, applinthService);
    container.set(Services.ORCHESTY_SERVICE, orchestyService);
    container.set(Services.CORRECTIONS_SERVICE, correctionService);
    container.set(Services.MODULE_SERVICE, moduleService);
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
