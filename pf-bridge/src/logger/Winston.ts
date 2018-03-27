import * as winston from "winston";

const nodeEnv = process.env.NODE_ENV || "prod";

let level;
switch (nodeEnv) {
    case "debug":
        level = "debug";
        break;
    case "test":
        level = "alert";
        break;
    default:
        level = "info";
}

const transports = [
    new (winston.transports.Console)({
        name: "pf-console",
        colorize: true,
        level,
    }),
];

const winstonLogger = new (winston.Logger)({ transports });

export default winstonLogger;
