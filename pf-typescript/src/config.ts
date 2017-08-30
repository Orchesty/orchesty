import {IOptions} from "lib-nodejs/dist/src/rabbitmq/Connection";

export const amqpConnectionOptions: IOptions = {
    host: "docker-pa",
    user: "guest",
    pass: "guest",
    port: 5672,
    vhost: "/",
    heartbeat: 60,
};
