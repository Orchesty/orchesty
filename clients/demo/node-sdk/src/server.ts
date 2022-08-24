import { listen } from '@orchesty/nodejs-sdk';
import { start } from './index';

// eslint-disable-next-line @typescript-eslint/no-floating-promises
start().then(listen);
