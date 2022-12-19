import express from 'express';
import { appOptions } from './Config/Config';
import { logger } from './logger/logger';

const expressApp: express.Application = express();

expressApp.use(express.json);

export function listen(): void {
    expressApp.disable('x-powered-by');
    expressApp.listen(appOptions.port, () => {
        logger.info(`⚡️[server]: Server is running at http://localhost:${appOptions.port}`);
    });
}

export { expressApp };
