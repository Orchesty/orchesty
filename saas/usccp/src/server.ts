import { app } from './config/config';
import { createServer, initServices } from './index';
import { logger } from './logger/logger';

initServices()
    .then(() => {
        const server = createServer();
        server.listen(app.port);
        logger.info('USCCP up and running!');
    }).catch((e: unknown) => {
        logger.error(e);
        process.exit(1);
    });
