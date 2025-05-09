import bodyParser from 'body-parser';
import express, { Express } from 'express';
import * as fs from 'fs';
import * as http from 'http';
import * as jsyaml from 'js-yaml';
import { configure, initialize } from 'oas-tools';
import { app } from './config/config';
import DIContainer from './DIContainer/Container';
import Services from './DIContainer/Services';
import EventService from './events/EventService';
import Storage from './storage/Storage';

const container = new DIContainer();

async function initServices(): Promise<void> {
    const storage = new Storage();
    await storage.init();
    const eventService = new EventService();
    container.set(Services.STORAGE, storage);
    container.set(Services.EVENT_SERVICE, eventService);
}

function createServer(): Express {
    const server = express();
    server.use(bodyParser.json({
        type: () => true, // because backend doesn't send a Content-type
    }));

    const spec = fs.readFileSync(app.openapiPath, 'utf8');
    const oasDoc = jsyaml.load(spec);

    configure({
        controllers: `${__dirname}/controllers`,
        checkControllers: true,
        loglevel: 'info',
        logfile: './logs',
        strict: false,
        router: true,
        validator: true,
        docs: {
            apiDocs: './zdf',
            apiDocsPrefix: '',
        },
    });

    initialize(oasDoc, server, () => {
        http.createServer();
    });

    container.set(Services.SERVER, server);
    return server;
}

export { container, createServer, initServices };
