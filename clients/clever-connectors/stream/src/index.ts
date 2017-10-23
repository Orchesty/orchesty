import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import StreamServer, {IStreamServerSettings} from "./StreamServer";
import Users from "./Users";

const settings: IStreamServerSettings = {
    port: parseInt(process.env.STREAM_WS_PORT, 10) || 8080,
    namespace: "/stream",
    subscribeTimeout: parseInt(process.env.STREAM_SUBSCRIBE_TIMEOUT, 10) || 5 * 60 * 1000,
    consumer: {
        queue: {
            name: process.env.STREAM_QUEUE || "stream",
            options: {},
        },
    },
    amqp: {
        host: process.env.RABBITMQ_HOST || "rabbitmq",
        user: process.env.RABBITMQ_USER || "guest",
        pass: process.env.RABBITMQ_PASS || "guest",
        port: parseInt(process.env.RABBITMQ_PORT, 10) || 5672,
        vhost: process.env.RABBITMQ_VHOST || "/",
        heartbeat: parseInt(process.env.RABBITMQ_HEARTBEAT, 10) || 60,
    },
    http: {
        port: parseInt(process.env.STREAM_HTTP_PORT, 10) || 3030,
        routes: {
            login: process.env.STREAM_ROUTE_LOGIN || "/login",
            logout: process.env.STREAM_ROUTE_LOGOUT || "/logout",
        },
    },
};

const users = new Users(settings.http);
const conn = new Connection(settings.amqp);
const stream = new StreamServer(settings, users, conn);

stream.start();
