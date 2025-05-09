import { listen } from '@orchesty/nodejs-sdk';
import logger from '@orchesty/nodejs-sdk/dist/lib/Logger/Logger';
import { initialize } from './index';

initialize()
    .then(listen)
    .catch((e: unknown) => {
        logger.error((e as Error).message, {});
        process.exit(1);
    });
