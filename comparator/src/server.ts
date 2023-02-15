import { listen } from '@orchesty/nodejs-sdk';
import { initialize } from './index';

void initialize().then(listen);
