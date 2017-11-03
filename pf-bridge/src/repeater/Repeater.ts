import {Channel, Message, Options} from "amqplib";
import {AssertionPublisher} from "amqplib-plus/dist/lib/AssertPublisher";
import {Connection, createChannelCallback} from "amqplib-plus/dist/lib/Connection";
import {SimpleConsumer} from "amqplib-plus/dist/lib/SimpleConsumer";
import {ObjectUtils} from "hb-utils/dist/lib/ObjectUtils";
import Headers from "../message/Headers";
import logger from "./../logger/Logger";
import IMessageStorage from "./IMessageStorage";

export interface IRepeaterSettings {
    input: {
        queue: {
            name: string;
            options: any;
        };
    };
    check_timeout: number;
}

class Repeater {

    private consumer: SimpleConsumer;
    private publisher: AssertionPublisher;

    /**
     *
     * @param {IRepeaterSettings} settings
     * @param {Connection} amqpCon
     * @param {IMessageStorage} storage
     */
    constructor(
        private settings: IRepeaterSettings,
        private amqpCon: Connection,
        private storage: IMessageStorage,
    ) {
        this.consumer = this.createConsumer();
        this.publisher = this.createPublisher();
    }

    /**
     * Check for messages to be reSent in infinite loop
     */
    public run() {
        this.consumer.consume(this.settings.input.queue.name, {})
            .then(() => {
                logger.info(
                    `Repeater consumer started consumption of messages in '${this.settings.input.queue.name}'`,
                    { node_id: "repeater" },
                );
                this.checkMessages();
            });
    }

    /**
     * Infinite checking loop
     */
    private checkMessages() {
        this.storage.findExpired()
            .then((toResend: Message[]) => {

                if (toResend.length < 1) {
                    return;
                }

                logger.info(
                    `Found ${toResend.length} messages to resend. Next check in ${this.settings.check_timeout}ms.`,
                    { node_id: "repeater" },
                );

                toResend.forEach((msg: Message) => {
                    this.resend(msg)
                        .then(() => {
                            const h = msg.properties.headers;
                            logger.info(
                                "Message repeated.",
                                { node_id: "repeater", correlation_id: h.correlation_id, process_id: h.process_id },
                            );
                        });
                });
            });

        setTimeout(() => { this.checkMessages(); }, this.settings.check_timeout);
    }

    /**
     *
     * @param {Message} message
     * @return {Promise<void>}
     */
    private resend(message: Message): Promise<void> {
        try {
            const headers = new Headers(message.properties.headers);
            const target = headers.getPFHeader(Headers.REPEAT_QUEUE);

            const content = new Buffer(message.content.toString());
            const props: Options.Publish = ObjectUtils.removeNullableProperties(message.properties);
            props.headers = headers.getRaw();
            props.priority ? props.priority++ : props.priority = 1;

            return this.publisher.sendToQueue(target, content, props);
        } catch (e) {
            const h = message.properties.headers;
            logger.error(
                "Repeater could not resend message",
                { node_id: "repeater", correlation_id: h.correlation_id, process_id: h.process_id },
            );

            return Promise.resolve();
        }
    }

    /**
     *
     * @return {SimpleConsumer}
     */
    private createConsumer() {
        const prepareFn: createChannelCallback = (ch: Channel) => {
            return new Promise((resolve) => {
                ch.assertQueue(this.settings.input.queue.name, this.settings.input.queue.options)
                    .then(() => {
                        logger.info("Repeater consumer ready.", { node_id: "repeater" });
                        resolve();
                    });
            });
        };

        const handleMessageFn = (msg: Message) => {
            const headers = new Headers(msg.properties.headers);

            if (!headers.hasPFHeader(Headers.REPEAT_QUEUE) || !headers.hasPFHeader(Headers.REPEAT_INTERVAL)) {
                logger.error(
                    `Repeater discarded message. Missing 'REPEAT_QUEUE' or 'REPEAT_INTERVAL' headers.
                     Headers: "${JSON.stringify(headers.getRaw())}"`,
                    {
                        node_id: "repeater",
                        correlation_id: headers.getPFHeader(Headers.CORRELATION_ID),
                        process_id: headers.getPFHeader(Headers.PROCESS_ID),
                    },
                );

                // Ignore this message and ack it
                return;
            }

            const timeout = parseInt(headers.getPFHeader(Headers.REPEAT_INTERVAL), 10);

            return this.storage.save(msg, timeout);
        };

        return new SimpleConsumer(this.amqpCon, prepareFn, handleMessageFn);
    }

    /**
     *
     * @return {AssertionPublisher}
     */
    private createPublisher() {
        return new AssertionPublisher(
            this.amqpCon,
            () => {
                logger.info("Repeater publisher ready.", { node_id: "repeater" });
                return Promise.resolve();
            },
        );
    }

}

export default Repeater;
