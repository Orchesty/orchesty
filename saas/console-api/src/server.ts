import { app } from './config/config';
import { createServer, initServices, logger } from './index';

// eslint-disable-next-line @typescript-eslint/no-floating-promises
initServices().then(() => {
    const server = createServer();
    server.listen(app.port);
    logger.info('Servers started');
});
