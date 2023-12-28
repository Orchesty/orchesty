import { listen } from '@orchesty/nodejs-sdk';
import logger from '@orchesty/nodejs-sdk/dist/lib/Logger/Logger';
import { start } from './index';

start();
listen();

process.on('unhandledRejection', (err) => {
  const error = err as Error;
  logger.error(error.message, {}, false, error);
  process.exit(1);
});
