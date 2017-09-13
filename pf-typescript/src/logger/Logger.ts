import * as winston from "winston";

const nodeEnv = process.env.NODE_ENV || "production";

let level;
switch (nodeEnv) {
    case "production":
        level = "info";
        break;
    case "test":
        level = "error";
        break;
    default:
        level = "debug";
}

const pfFormatter = (options: any) => {
    return JSON.stringify({
        timestamp: Date.now(),
        severity: options.level,
        message: options.message,
        hostname: "localhost", // TODO - use os.host
        type: "type", // TODO - use process.env
        stacktrace: "", // TODO - how to pass this
    });
};

const transports = [
    new (winston.transports.Console)({
        colorize: true,
        level,
        formatter: pfFormatter,
    }),
];

const logger = new (winston.Logger)({ transports });

export default logger;
