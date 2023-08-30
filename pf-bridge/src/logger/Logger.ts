import {ILogger} from "amqplib-plus/dist/ILogger";
import {Sender} from "metrics-sender/dist/lib/udp/Sender";
import * as os from "os";
import {loggerOptions} from "../config";
import JobMessage from "../message/JobMessage";
import {ResultCode} from "../message/ResultCode";
import {default as winston} from "./Winston";

export interface ILogContext {
    topology_id?: string;
    node_id?: string;
    node_name?: string;
    correlation_id?: string;
    process_id?: string;
    parent_id?: string;
    sequence_id?: number;
    result_code?: ResultCode;
    result_message?: string;
    error?: any;
    data?: string;
}

interface ILoggerFormat {
    timestamp: number;
    hostname: string;
    type: string;
    severity: string;
    message: string;
    node_id?: string;
    node_name?: string;
    correlation_id?: string;
    result_code?: ResultCode;
    result_message?: string;
    stacktrace?: {
        message: string,
        trace?: string,
    };
    data?: string;
}

class Logger implements ILogger {

    /**
     *
     * @param {string} severity
     * @param {string} message
     * @param {ILogContext} context
     * @return {ILoggerFormat}
     */
    private static format(severity: string, message: string, context?: ILogContext): ILoggerFormat {
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

        if (context.node_name) {
            line.node_name = context.node_name;
        }

        if (context.result_code >= 0) {
            line.result_code = context.result_code;
        }

        if (context.result_message) {
            line.result_message = context.result_message;
        }

        if (context.error) {
            if (context.error instanceof Object) {
                line.stacktrace = {
                    message: '',
                    trace: ''
                };
                if (context.error.hasOwnProperty('message')) {
                    line.stacktrace.message = context.error.message.replace( /\s\s+/g, " ");
                }
                if (context.error.hasOwnProperty('stack')) {
                    line.stacktrace.trace = context.error.stack;
                }
            } else {
                line.stacktrace =  {
                    message: `${context.error}`.toString(),
                };
            }
        }

        if (context.data) {
            line.data = context.data.slice(0, 100_000);
        }

        return line;
    }

    private udp: Sender;

    private readonly isDebug: boolean;

    constructor() {
        this.udp = new Sender(loggerOptions.server, loggerOptions.port);
        this.isDebug = process.env.NODE_ENV === "test" || process.env.NODE_ENV === "dev";
    }

    /**
     *
     * @param {string} message
     * @param {ILogContext} context
     */
    public debug(message: string, context?: ILogContext): void {
        if (this.isDebug) {
            this.log("debug", message, context ? context : {});
        }
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
    public log(severity: string, message: string, context?: ILogContext): void {
        const data = Logger.format(severity, message, context);

        winston.log(severity,'', data);
        this.udp.send(JSON.stringify(data))
            .catch(() => {
                // unhandled promise rejection caught
            });
    }

}

const logger = new Logger();

export default logger;