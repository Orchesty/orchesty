
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import {amqpConnectionOptions} from "../config";
import StreamServer, {IStreamServerSettings} from "../stream/StreamServer";

const conn = new Connection(amqpConnectionOptions);
const settings: IStreamServerSettings = {
    port: 8080,
    consumer: {
        queue: {
            name: "stream_input",
            options: {},
        },
    },
};

const stream = new StreamServer(settings, conn);

stream.start();
