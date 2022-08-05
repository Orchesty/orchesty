import winston, { Logger } from 'winston';

export enum Severity {
  INFO= 'info',
  DEBUG= 'debug'
}

export default function initializeLogger(debug: boolean): Logger {
  return winston.createLogger(
    {
      level: debug ? Severity.DEBUG : Severity.INFO,
      transports: [
        new winston.transports.Console({ format: winston.format.splat() }),
      ],
    },
  );
}
