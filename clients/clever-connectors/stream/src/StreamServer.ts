import {Message} from "amqplib";
import logger from "lib-nodejs/dist/src/logger/Logger";
import Connection, {IOptions} from "lib-nodejs/dist/src/rabbitmq/Connection";
import * as SocketIO from "socket.io";
import StreamConsumer, {IStreamConsumerSettings} from "./StreamConsumer";
import {default as Users, IStreamHttpServerSettings} from "./Users";

export interface IStreamServerSettings {
    port: number;
    namespace: string;
    subscribeTimeout: number;
    consumer: IStreamConsumerSettings;
    amqp: IOptions;
    http: IStreamHttpServerSettings;
}

export interface IStreamMessage {
    event: string;
    groups: string[];
    content: string;
}

export interface ISubscribeData {
    token: string;
    groups: string[];
}

export enum STREAM_EVENTS {
    SUBSCRIBE = "subscribe",
    MESSAGE = "message",
    ERROR_MESSAGE = "error_message",
    INFO_MESSAGE = "info_message",
    UNSUBSCRIBE = "unsubscribe",
}

class StreamServer {

    /**
     *
     * @param body
     * @return {boolean}
     */
    private static isMessageValid(body: any): boolean {
        if (!body.groups || !Array.isArray(body.groups)) {
            logger.error(`Invalid stream message 'groups'.`);
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
    private timeouts: { [key: string]: NodeJS.Timer };

    /**
     *
     * @param {IStreamServerSettings} settings
     * @param {Users} users
     * @param {AMQPConnection} connection
     */
    constructor(
        private settings: IStreamServerSettings,
        private users: Users,
        private connection: Connection,
    ) {
        this.timeouts = {};
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

            socket.on(STREAM_EVENTS.SUBSCRIBE, (data: ISubscribeData) => {
                logger.info(`Subscribe socket request ${socket.id}. Data: ${JSON.stringify(data)}`);
                this.subscribe(socket, data);
            });

            socket.on(STREAM_EVENTS.UNSUBSCRIBE, (data: ISubscribeData) => {
                logger.info(`Unsubscribe socket request ${socket.id}. Data: ${JSON.stringify(data)}`);
                this.unsubscribe(socket, data);
            });

            socket.on("disconnect", (reason) => {
                logger.info(`Disconnect socket ${socket.id}. Reason: ${reason}`);
            });
        });

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
            logger.error(`Could not parse message.`, err.message);
            return;
        }

        // Send to ws clients
        body.groups.forEach((group: string) => {
            logger.info(`Sending message[event=${body.event}, groups=${body.groups.join(",")}]`);
            this.stream.to(group).emit(STREAM_EVENTS.MESSAGE, { event: body.event, content: body.content });
        });
    }

    /**
     *
     * @param {SocketIO.Socket} socket
     * @param {ISubscribeData} data
     */
    private subscribe(socket: SocketIO.Socket, data: ISubscribeData): void {
        if (!this.users.isValidToken(data.token)) {
            logger.warn(`Trying to subscribe with invalid token: "${data.token}"`);

            return;
        }

        let userId: string;
        try {
            userId = this.users.getUserId(data.token);
            socket.join(userId);

            logger.info(`User[token=${data.token}, userId=${userId}] subscribed to group '${userId}'`);
        } catch (joinErr) {
            logger.error(`Could not find userId for token "${data.token}". Error: ${joinErr.message}`);
            socket.emit(STREAM_EVENTS.ERROR_MESSAGE, `Trying to subscribe with invalid token "${data.token}."`);

            return;
        }

        if (data.groups && data.groups.length > 0) {
            data.groups.forEach((groupId) => {
                if (this.users.canAccessGroup(data.token, groupId)) {
                    socket.join(groupId);
                    socket.emit(STREAM_EVENTS.INFO_MESSAGE, `You subscribed to group "${groupId}"`);

                    logger.info(`User[token=${data.token}, userId=${userId}] subscribed to group '${groupId}'`);
                } else {
                    socket.emit(STREAM_EVENTS.ERROR_MESSAGE, `You are not allowed to subscribe to group "${groupId}"`);
                    logger.warn(`User[token=${data.token}, userId=${userId}] not allowed to subscribe '${groupId}'`);
                }
            });

            this.removeTimeout(data.token);
            this.addTimeout(socket, data);
        }
    }

    /**
     *
     * @param {SocketIO.Socket} socket
     * @param {ISubscribeData} data
     */
    private unsubscribe(socket: SocketIO.Socket, data: ISubscribeData): void {
        if (!this.users.isValidToken(data.token)) {
            logger.warn(`Trying to unsubscribe with invalid token: "${data.token}"`);

            return;
        }

        let userId: string;
        try {
            userId = this.users.getUserId(data.token);
            socket.leave(userId);
        } catch (joinErr) {
            logger.error(`Cannot find user. Error: ${joinErr.message}`);
            return;
        }

        if (data.groups && data.groups.length > 0) {
            this.removeTimeout(data.token);

            data.groups.forEach((groupId) => {
                socket.emit(STREAM_EVENTS.INFO_MESSAGE, `You unsubscribed from group "${groupId}"`);
                socket.leave(groupId);

                logger.info(`User[token=${data.token}, userId=${userId}] unsubscribed from group '${groupId}'`);
                logger.info(`Token '${data.token}' unsubscribed from group '${groupId}'`);
            });
        }
    }

    /**
     *
     * @param {SocketIO.Socket} socket
     * @param {ISubscribeData} data
     */
    private addTimeout(socket: SocketIO.Socket, data: ISubscribeData) {
        this.timeouts[data.token] = setTimeout(
            () => {
                this.unsubscribe(socket, data);
            },
            this.settings.subscribeTimeout,
        );
    }

    /**
     *
     * @param {string} token
     */
    private removeTimeout(token: string) {
        delete this.timeouts[token];
    }

}

export default StreamServer;
