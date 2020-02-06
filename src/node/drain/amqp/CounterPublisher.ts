import {Channel, Options} from "amqplib";
import {Connection, Publisher} from "amqplib-plus";
import logger from "../../../logger/Logger";
import CounterMessage from "../../../message/CounterMessage";
import Headers from "../../../message/Headers";
import JobMessage from "../../../message/JobMessage";
import {INodeLabel} from "../../../topology/Configurator";

export interface ICounterPublisherSettings {
    node_label: INodeLabel;
    persistent: boolean;
    counter: {
        queue: {
            name: string;
            options: any;
        };
    };
    followers: any[];
}

const IS_CONFIRM_CHANNEL = false;

export interface ICounterPublisher {

    /**
     *
     * @param {JobMessage} message
     * @param {number} followersCount
     * @return {Promise<void>}
     */
    send(message: JobMessage, followersCount?: number): Promise<void>;
}

/**
 * This class will be injected to all drains and all counter result messages will be published using it
 */
class CounterPublisher extends Publisher implements ICounterPublisher {

    private settings: ICounterPublisherSettings;

    /**
     *
     * @param {Connection} conn
     * @param {ICounterPublisherSettings} settings
     */
    constructor(conn: Connection, settings: ICounterPublisherSettings) {
        super(
            conn,
            (ch: Channel) => {
                const q = settings.counter.queue;

                return new Promise(async (resolve) => {
                    await ch.assertQueue(q.name, q.options);
                    resolve();
                });
            },
            IS_CONFIRM_CHANNEL,
            console,
        );
        this.settings = settings;
    }

    /**
     * Sends the counter info message
     *
     * @param {JobMessage} message
     * @param {number} forceFollowersCount
     * @return {Promise<void>}
     */
    public send(message: JobMessage, forceFollowersCount: number = null): Promise<void> {
        message.getHeaders().setPFHeader(Headers.TOPOLOGY_ID, this.settings.node_label.topology_id);

        let followers = this.settings.followers.length;
        if (forceFollowersCount !== null && typeof forceFollowersCount === "number" && forceFollowersCount >= 0) {
            followers = forceFollowersCount;
        }

        const counterMessage = new CounterMessage(
            this.settings.node_label,
            message.getHeaders().getRaw(),
            message.getResult().code, // 0 OK, >0 NOK
            message.getResult().message,
            followers,
            message.getMultiplier(),
            message.getResult().code,
            message.getRequest(),
            message.getResponse()
        );

        const opts: Options.Publish = {
            headers: counterMessage.getHeaders().getRaw(),
            type: "counter_message",
            timestamp: Date.now(),
            appId: this.settings.node_label.id,
            persistent: this.settings.persistent,
        };

        logger.debug(
            `Counter publisher - sending message. ${JSON.stringify(counterMessage)}`,
            logger.ctxFromMsg(message),
        );

        return this.sendToQueue(
            this.settings.counter.queue.name,
            Buffer.from(counterMessage.getContent()),
            opts,
        );
    }

}

export default CounterPublisher;
