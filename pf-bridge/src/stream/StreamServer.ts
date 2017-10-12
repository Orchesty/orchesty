import {Message} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import * as SocketIO from "socket.io";
import logger from "../logger/Logger";
import StreamConsumer, {IStreamConsumerSettings} from "./StreamConsumer";

export interface IStreamServerSettings {
    port: number;
    namespace: string;
    consumer: IStreamConsumerSettings;
}

export interface IStreamMessage {
    event: string;
    recipients: string[];
    content: string;
}

interface ISubscribeData {
    userId: string;
    groups: string[];
}

interface IUnsubscribeData {
    userId: string;
    groups: string[];
}

class StreamServer {

    /**
     *
     * @param body
     * @return {boolean}
     */
    private static isMessageValid(body: any): boolean {
        if (!body.recipients || !Array.isArray(body.recipients)) {
            logger.error(`Invalid stream message 'recipients'.`);
            return false;
        }

        if (!body.event) {
            logger.error(`Invalid stream message 'event'.`);
            return false;
        }

        if (!body.content) {
            logger.error(`Invalid stream message 'content'.`);
            return false;
        }

        return true;
    }

    private consumer: StreamConsumer;
    private stream: SocketIO.Namespace;

    /**
     *
     * @param {IStreamServerSettings} settings
     * @param {AMQPConnection} connection
     */
    constructor(
        private settings: IStreamServerSettings,
        connection: Connection,
    ) {
        this.consumer = new StreamConsumer(
            settings.consumer,
            connection,
            (msg: Message) => {
                this.processInputMessage(msg);
            },
        );

        const io = SocketIO(this.settings.port);
        this.stream = io.of(settings.namespace);
    }

    /**
     *
     */
    public start() {

        this.stream.on("connection", (socket: SocketIO.Socket) => {

            socket.on("subscribe", (data: ISubscribeData) => {
                logger.info(`Subscribe socket ${socket.id}. Data: ${JSON.stringify(data)}`);

                if (data.groups && data.groups.length > 0) {
                    data.groups.forEach((groupId) => {
                        socket.join(groupId);
                    });
                }
            });

            socket.on("unsubscribe", (data: IUnsubscribeData) => {
                logger.info(`Unsubscribe socket ${socket.id}. Data: ${JSON.stringify(data)}`);
            });

            socket.on("disconnect", (reason) => {
                logger.info(`Disconnect socket ${socket.id}. Reason: ${reason}`);
            });
        });

        // todo - delay consuming until some clients are connected?
        this.consumer.start();
    }

    /**
     *
     * @param {Message} msg
     */
    public processInputMessage(msg: Message): void {
        let body: IStreamMessage;
        try {
            body = JSON.parse(msg.content.toString());

            if (!StreamServer.isMessageValid(body)) {
                return;
            }
        } catch (err) {
            logger.error(`Could not parse message.`, { error: err });
        }

        // Send to ws clients
        body.recipients.forEach((group: string) => {
            this.stream.to(group).emit("message", { event: body.event, content: body.content });
        });
    }

}

export default StreamServer;
