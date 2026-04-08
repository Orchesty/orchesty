import { listen } from '@orchesty/nodejs-sdk';
import logger from '@orchesty/nodejs-sdk/dist/lib/Logger/Logger';
import { start } from './index';

(async () => {
    await start();
    await listen();
})().catch((e: unknown) => {
    const error = e as Error;
    logger.error(error.message, {}, false, error);
    process.exit(1);
});
