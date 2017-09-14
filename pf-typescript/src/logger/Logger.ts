import {default as winston } from "./Winston";

export interface ILogContext {
    node_id?: string;
    correlation_id?: string;
    error?: Error;
}

class Logger {

    public debug(message: string, context?: ILogContext): void {
        winston.debug(message, context ? context : {});
    }

    public info(message: string, context?: ILogContext): void {
        winston.info(message, context ? context : {});
    }

    public warn(message: string, context?: ILogContext): void {
        winston.warn(message, context ? context : {});
    }

    public error(message: string, context?: ILogContext): void {
        winston.error(message, context ? context : {});
    }

}

const logger = new Logger();

export default logger;
