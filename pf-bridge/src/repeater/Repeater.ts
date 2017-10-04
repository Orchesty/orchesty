import {Channel, Message} from "amqplib";
import AMQPConnection from "lib-nodejs/dist/src/rabbitmq/Connection";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import logger from "./../logger/Logger";
import AssertionPublisher from "./AssertPublisher";
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
                            logger.info("Message resent.", { node_id: "repeater" });
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
        return this.publisher.assertQueueAndSend("something", {}, message.content, message.properties);
    }

    /**
     *
     * @return {SimpleConsumer}
     */
    private createConsumer() {
        return new SimpleConsumer(
            this.amqpCon,
            (ch: Channel) => {
                return new Promise((resolve) => {
                    ch.assertQueue(this.settings.input.queue.name, this.settings.input.queue.options)
                        .then(() => {
                            logger.info("Repeater consumer ready.", { node_id: "repeater" });
                            resolve();
                        });
                });
            },
            (msg: Message) => {
                return this.storage.save(msg);
            },
        );
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
