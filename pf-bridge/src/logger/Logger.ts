import Sender from "lib-nodejs/dist/src/udp/Sender";
import * as os from "os";
import {loggerOptions} from "../config";
import {default as winston} from "./Winston";

export interface ILogContext {
    node_id?: string;
    correlation_id?: string;
    error?: Error;
}

class Logger {

    /**
     *
     * @param {string} severity
     * @param {string} message
     * @param {ILogContext} context
     * @return {string}
     */
    private static format(severity: string, message: string, context?: ILogContext): string {
        const line = {
            timestamp: Date.now(),
            hostname: os.hostname(),
            type: process.env.PIPES_NODE_TYPE || "pipes_node",
            severity: `${severity}`.toUpperCase(),
            message: message.replace( /\s\s+/g, " "),
            node_id: "",
            correlation_id: "",
            stacktrace: {},
        };

        if (context.correlation_id) {
            line.correlation_id = context.correlation_id;
        }

        if (context.node_id) {
            line.node_id = context.node_id;
        }

        if (context.error) {
            if (context.error instanceof Error) {
                line.stacktrace =  {
                    message: context.error.message.replace( /\s\s+/g, " "),
                    trace: context.error.stack,
                };
            } else {
                line.stacktrace =  {
                    message: `${context.error}`.toString(),
                };
            }
        }

        return JSON.stringify(line);
    }

    private udp: Sender;

    constructor() {
        this.udp = new Sender(loggerOptions.server, loggerOptions.port);
    }

    /**
     *
     * @param {string} message
     * @param {ILogContext} context
     */
    public debug(message: string, context?: ILogContext): void {
        this.log("debug", message, context ? context : {});
    }

    /**
     *
     * @param {string} message
     * @param {ILogContext} context
     */
    public info(message: string, context?: ILogContext): void {
        this.log("info", message, context ? context : {});
    }

    /**
     *
     * @param {string} message
     * @param {ILogContext} context
     */
    public warn(message: string, context?: ILogContext): void {
        this.log("warn", message, context ? context : {});
    }

    /**
     *
     * @param {string} message
     * @param {ILogContext} context
     */
    public error(message: string, context?: ILogContext): void {
        this.log("error", message, context ? context : {});
    }

    /**
     *
     * @param {string} severity
     * @param {string} message
     * @param {ILogContext} context
     */
    private log(severity: string, message: string, context?: ILogContext): void {
        const data = Logger.format(severity, message, context);

        winston.log(severity, data);
        this.udp.send(data)
            .catch(() => {
                // unhandled promise rejection caught
            });
    }

}

const logger = new Logger();

export default logger;
