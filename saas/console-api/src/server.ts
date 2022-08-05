import { initServices, logger, createServer } from './index';
import { app } from './config/config';

initServices().then(() => {
  const server = createServer();
  server.listen(app.port);
  logger.info('Servers started');
});
