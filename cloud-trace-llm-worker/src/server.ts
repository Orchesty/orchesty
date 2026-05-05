import { listen } from '@orchesty/nodejs-sdk';
import logger from '@orchesty/nodejs-sdk/dist/lib/Logger/Logger';
import { prepare } from './index';

(async () => {
    prepare();
    await listen();
})().catch((e: unknown) => {
    const error = e as Error;
    logger.error(error.message, {}, false, error);
    process.exit(1);
});
