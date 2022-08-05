import { listen } from '@orchesty/nodejs-sdk';
import { start } from './index';

start().then(listen);
