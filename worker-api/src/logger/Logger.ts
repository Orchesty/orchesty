import pino from 'pino';
import { appOptions } from '../config/Config';

export const logger = pino({ level: appOptions.debug ? 'debug' : 'info' });
