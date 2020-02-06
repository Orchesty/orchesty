import {IConnectionOptions} from "amqplib-plus";
import {IRedisStorageSettings} from "./counter/storage/RedisStorage";
import {ILimiterSettings} from "./limiter/Limiter";
import {IProbeSettings} from "./probe/Probe";
import {IRepeaterSettings} from "./repeater/Repeater";
import {IMongoMessageStorageSettings} from "./repeater/storage/MongoMessageStorage";

// use production by default, some libraries are optimized for better performance in production env
process.env.NODE_ENV = process.env.NODE_ENV || "production";

// Set timeouts and other env values differently for tests
if (process.env.NODE_ENV === "test") {
    process.env.REPEATER_CHECK_TIMEOUT = "500";
}

// enable persistent mode until it is not disabled
export const persistentQueues: boolean = !(process.env.PERSISTENT_QUEUES === "false"); // default true
export const persistentMessages: boolean = !(process.env.PERSISTENT_MESSAGES === "false"); // default true

export const amqpConnectionOptions: IConnectionOptions = {
    host: process.env.RABBITMQ_HOST || "rabbitmq",
    user: process.env.RABBITMQ_USER || "guest",
    pass: process.env.RABBITMQ_PASS || "guest",
    port: parseInt(process.env.RABBITMQ_PORT, 10) || 5672,
    vhost: process.env.RABBITMQ_VHOST || "/",
    heartbeat: parseInt(process.env.RABBITMQ_HEARTBEAT, 10) || 60,
};

export const amqpFaucetOptions = {
    prefetch: parseInt(process.env.FAUCET_PREFETCH, 10) || 5,
    dead_letter_exchange: { name: "pipes.dead-letter", type: "direct", options: {} },
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
            options: {
                durable: persistentQueues,
            },
        },
    },
    check_timeout: parseInt(process.env.REPEATER_CHECK_TIMEOUT, 10) || 5 * 1000,
};

export const mongoStorageOptions: IMongoMessageStorageSettings = {
    host: process.env.MONGO_HOST || "mongo",
    port: parseInt(process.env.MONGO_PORT, 10) || 27017,
    user: process.env.MONGO_USER || "",
    pass: process.env.MONGO_PASS || "",
    db: process.env.MONGO_DB || "repeater",
};

export const probeOptions: IProbeSettings = {
    port: parseInt(process.env.PROBE_PORT, 10) || 8007,
    path: process.env.PROBE_PATH || "/status",
    timeout: parseInt(process.env.PROBE_TIMEOUT, 10) || 10000,
};

export const multiProbeOptions = {
    host: process.env.MULTI_PROBE_HOST || "multi-probe",
    port: parseInt(process.env.PROBE_PORT, 10) || 8007,
};

export const topologyTerminatorOptions = {
    port: parseInt(process.env.TERMINATOR_PORT, 10) || 8005,
};

export const redisStorageOptions: IRedisStorageSettings = {
    host: process.env.REDIS_HOST || "redis",
    port: parseInt(process.env.REDIS_PORT, 10) || 6379,
    password: process.env.REDIS_PASS || "",
    db: parseInt(process.env.REDIS_DB, 10) || 0,
};

export const limiterOptions: ILimiterSettings = {
    host: process.env.LIMITER_HOST || "limiter",
    port: parseInt(process.env.LIMITER_PORT, 10) || 3333,
    queue: {
        name: process.env.LIMITER_QUEUE || "pipes.limiter",
        options: {},
    },
};

export const counterOptions = {
    prefetch: parseInt(process.env.COUNTER_PREFETCH, 10) || 10,
};
