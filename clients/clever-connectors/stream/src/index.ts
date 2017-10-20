import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import StreamServer, {IStreamServerSettings} from "./StreamServer";
import Users from "./Users";

const settings: IStreamServerSettings = {
    port: 8080,
    namespace: "/stream",
    consumer: {
        queue: {
            name: "cc_stream",
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
        port: 3030,
        routes: {
            login: "/login",
            logout: "/logout",
        },
    },
};

const users = new Users(settings.http);
const conn = new Connection(settings.amqp);
const stream = new StreamServer(settings, users, conn);

stream.start();
