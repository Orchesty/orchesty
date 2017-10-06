import {Channel, Message} from "amqplib";
import AssertionPublisher from "lib-nodejs/dist/src/rabbitmq/AssertPublisher";
import AMQPConnection, {PrepareFn} from "lib-nodejs/dist/src/rabbitmq/Connection";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import logger from "./../logger/Logger";
import IMessageStorage from "./IMessageStorage";
import ObjectUtils from "lib-nodejs/dist/src/utils/ObjectUtils";

process.on('unhandledRejection', (reason) => {
    console.log('Reason: ' + reason);
});

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
     * @param {AMQPConnection} amqpCon
     * @param {IMessageStorage} storage
     */
    constructor(
        private settings: IRepeaterSettings,
        private amqpCon: AMQPConnection,
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
            const target = message.properties.replyTo;
            const content = new Buffer(message.content.toString());
            const props = ObjectUtils.removeNullableProperties(message.properties);

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
        const prepareFn: PrepareFn = (ch: Channel) => {
            return new Promise((resolve) => {
                ch.assertQueue(this.settings.input.queue.name, this.settings.input.queue.options)
                    .then(() => {
                        logger.info("Repeater consumer ready.", { node_id: "repeater" });
                        resolve();
                    });
            });
        };

        const handleMessageFn = (msg: Message) => {
            const headers = msg.properties.headers;

            if (!headers.repeat_interval || !msg.properties.replyTo) {
                logger.error(
                    "Repeater discarded message. Missing 'repeat_interval' or 'reply_to' header.",
                    { node_id: "repeater", correlation_id: headers.correlation_id, process_id: headers.process_id },
                );

                return;
            }

            const timeout = parseInt(msg.properties.headers.repeat_interval, 10);

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
