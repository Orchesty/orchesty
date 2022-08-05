import express, { Express, RequestHandler } from 'express';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import { configure, initializeMiddleware } from 'oas-tools';
import * as fs from 'fs';
import jsyaml from 'js-yaml';
import admin from 'firebase-admin';
import { initializeApp } from 'firebase/app';
import Mongo from './storage/mongo/Mongo';
import UsageStatsService from './usageStats/UsageStatsService';
import { app, firebase, mongo } from './config/config';
import initializeLogger from './logger/logger';
import UsersService from './users/UsersService';
import TenantService from './tenants/TenantService';

/* eslint-disable import/no-mutable-exports */
let db: Mongo;
let usageStatsService: UsageStatsService;
let usersService: UsersService;
let tenantService: TenantService;
let server: Express;
const logger = initializeLogger(app.debug);
/* eslint-enable import/no-mutable-exports */

const authApp = admin.initializeApp({
  credential: admin.credential.cert(`${__dirname}/../privateKey.json`),
});

const fbApp = initializeApp({
  apiKey: firebase.apiKey,
});

async function initServices() {
  db = new Mongo(mongo.dsn);
  await db.connect();
  await db.createIndexes();
  logger.info('Database connected');
  usageStatsService = new UsageStatsService(db);
  usersService = new UsersService();
  tenantService = new TenantService();
}

function createServer(): Express {
  server = express();

  const spec = fs.readFileSync(app.openapiPath, 'utf8');
  const oasDoc = jsyaml.load(spec);
  const options = {
    controllers: `${__dirname}/controllers`,
  };
  configure(options);

  initializeMiddleware(oasDoc, server, (middleware: {swaggerRouter: RequestHandler}) => {
    server.use(middleware.swaggerRouter);
  });

  return server;
}

export {
  db, usageStatsService, usersService, tenantService, logger, server, createServer, initServices, authApp, fbApp,
};
