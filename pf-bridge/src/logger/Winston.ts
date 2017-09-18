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

const transports = [
    new (winston.transports.Console)({
        name: "pf-console",
        colorize: true,
        level,
    }),
];

const winstonLogger = new (winston.Logger)({ transports });

// Do not output anything when running test
if (nodeEnv === "test") {
    winstonLogger.remove(transports[0]);
}

export default winstonLogger;
