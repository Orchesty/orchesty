import {IOptions} from "lib-nodejs/dist/src/rabbitmq/Connection";

export const amqpConnectionOptions: IOptions = {
    host: process.env.RABBITMQ_HOST || "localhost",
    user: process.env.RABBITMQ_USER || "guest",
    pass: process.env.RABBITMQ_PASS || "guest",
    port: parseInt(process.env.RABBITMQ_PORT, 10) || 5672,
    vhost: process.env.RABBITMQ_VHOST || "/",
    heartbeat: parseInt(process.env.RABBITMQ_HEARTBEAT, 10) || 60,
};

export const metricsOptions = {
    measurement: process.env.METRICS_MEASUREMENT || "pipes_node",
    server: process.env.METRICS_HOST || "influxdb",
    port: parseInt(process.env.METRICS_PORT, 10) || 8089,
};
