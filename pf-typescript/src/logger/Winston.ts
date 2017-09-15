import * as os from "os";
import * as winston from "winston";

const nodeEnv = process.env.NODE_ENV || "production";

let level;
switch (nodeEnv) {
    case "production":
        level = "info";
        break;
    case "test":
        level = "warn";
        break;
    default:
        level = "debug";
}

const pfFormatter = (options: any) => {
    const line = {
        timestamp: Date.now(),
        hostname: os.hostname(),
        type: process.env.PIPES_NODE_TYPE || "pipes_node",
        severity: `${options.level}`.toUpperCase(),
        message: options.message.replace( /\s\s+/g, " "),
        node_id: "",
        correlation_id: "",
        stacktrace: {},
    };

    if (options.meta.correlation_id) {
        line.correlation_id = options.meta.correlation_id;
    }

    if (options.meta.node_id) {
        line.node_id = options.meta.node_id;
    }

    if (options.meta.error) {
        if (options.meta.error instanceof Error) {
            line.stacktrace =  {
                message: options.meta.error.message.replace( /\s\s+/g, " "),
                code: options.meta.error.code,
                file: options.meta.error.fileName,
                trace: options.meta.error.stack,
            };
        } else {
            line.stacktrace =  {
                message: options.meta.error.toString(),
            };
        }
    }

    return JSON.stringify(line);
};

const transports = [
    new (winston.transports.Console)({
        name: "pf",
        colorize: true,
        level,
        formatter: pfFormatter,
    }),
];

const winstonLogger = new (winston.Logger)({ transports });

// Do not output anything when running test
if (nodeEnv === "test") {
    winstonLogger.remove(transports[0]);
}

export default winstonLogger;
