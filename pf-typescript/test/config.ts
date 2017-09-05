import {IOptions} from "lib-nodejs/dist/src/rabbitmq/Connection";

export const testAmqpConnectionOptions: IOptions = {
    host: process.env.RABBITMQ_HOST || "docker-pa",
    user: "guest",
    pass: "guest",
    port: 5672,
    vhost: "/",
    heartbeat: 60,
};
