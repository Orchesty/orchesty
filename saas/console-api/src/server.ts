import { app } from './base/config/config';
import { logger } from './base/logger/logger';
import { createServer, initServices } from './index';

initServices()
    .then(() => {
        const server = createServer();
        server.listen(app.port);
        logger.info('Servers started');
    }).catch((e: unknown) => {
        logger.error(e);
    });
