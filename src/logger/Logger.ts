import Sender from "lib-nodejs/dist/src/udp/Sender";
import * as os from "os";
import {loggerOptions} from "../config";
import JobMessage from "../message/JobMessage";
import {default as winston} from "./Winston";

export interface ILogContext {
    node_id?: string;
    correlation_id?: string;
    process_id?: string;
    parent_id?: string;
    sequence_id?: number;
    error?: Error;
}

interface ILoggerFormat {
    timestamp: number;
    hostname: string;
    type: string;
    severity: string;
    message: string;
    node_id?: string;
    correlation_id?: string;
    stacktrace?: {
        message: string,
        trace?: string,
    };
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
        const line: ILoggerFormat = {
            timestamp: Date.now(),
            hostname: os.hostname(),
            type: process.env.PIPES_NODE_TYPE || "pipes_node",
            severity: `${severity}`.toUpperCase(),
            message: message.replace( /\s\s+/g, " "),
        };

        if (context.node_id) {
            line.node_id = context.node_id;
        }

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
     * @param {JobMessage} msg
     * @param {Error} err
     * @return {ILogContext}
     */
    public ctxFromMsg(msg: JobMessage, err?: Error): ILogContext {
        const ctx: ILogContext = {
            node_id: msg.getNodeId(),
            correlation_id: msg.getCorrelationId(),
            process_id: msg.getProcessId(),
            parent_id: msg.getParentId(),
            sequence_id: msg.getSequenceId(),
        };

        if (err) {
            ctx.error = err;
        }

        return ctx;
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
