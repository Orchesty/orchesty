import {IOptions} from "lib-nodejs/dist/src/rabbitmq/Connection";
import {IMongoMessageStorageSettings} from "./repeater/MongoMessageStorage";
import {IRepeaterSettings} from "./repeater/Repeater";

export const amqpConnectionOptions: IOptions = {
    host: process.env.RABBITMQ_HOST || "rabbitmq",
    user: process.env.RABBITMQ_USER || "guest",
    pass: process.env.RABBITMQ_PASS || "guest",
    port: parseInt(process.env.RABBITMQ_PORT, 10) || 5672,
    vhost: process.env.RABBITMQ_VHOST || "/",
    heartbeat: parseInt(process.env.RABBITMQ_HEARTBEAT, 10) || 60,
};

export const metricsOptions = {
    node_measurement: process.env.METRICS_MEASUREMENT || "pipes_node",
    counter_measurement: process.env.COUNTER_MEASUREMENT || "pipes_counter",
    server: process.env.METRICS_HOST || "influxdb",
    port: parseInt(process.env.METRICS_PORT, 10) || 8089,
};

export const loggerOptions = {
    server: process.env.UDP_LOGGER_HOST || "logstash",
    port: parseInt(process.env.UDP_LOGGER_PORT, 10) || 5120,
};

export const repeaterOptions: IRepeaterSettings = {
    input: {
        queue: {
            name: process.env.REPEATER_INPUT_QUEUE || "pipes.repeater",
            options: {},
        },
    },
    check_timeout: 10 * 1000,
};

export const mongoStorageOptions: IMongoMessageStorageSettings = {
    host: process.env.MONGO_HOST || "mongo",
    port: parseInt(process.env.MONGO_PORT, 10) || 27017,
    user: process.env.MONGO_USER || "",
    pass: process.env.MONGO_PASS || "",
    db: process.env.MONGO_DB || "repeater",
};
