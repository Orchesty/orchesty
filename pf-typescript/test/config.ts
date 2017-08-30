import {IOptions} from "lib-nodejs/dist/src/rabbitmq/Connection";

export const testAmqpConnectionOptions: IOptions = {
    host: "docker-pa",
    user: "guest",
    pass: "guest",
    port: 5672,
    vhost: "/",
    heartbeat: 60,
};
