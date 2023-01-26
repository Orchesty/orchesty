import pino from 'pino';
import { app } from '../config/config';

export const logger = pino({ level: app.debug ? 'debug' : 'info' });
