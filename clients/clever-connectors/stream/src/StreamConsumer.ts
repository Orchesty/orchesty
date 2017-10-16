import {Channel, Message} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";

export interface IStreamConsumerSettings {
    queue: {
        name: string;
        options: {};
    };
}

class StreamConsumer extends SimpleConsumer {

    /**
     *
     * @param {IStreamConsumerSettings} settings
     * @param {AMQPConnection} connection
     * @param {(msg: Message) => void} processMessageFn
     */
    constructor(
        private settings: IStreamConsumerSettings,
        connection: Connection,
        processMessageFn: (msg: Message) => void,
    ) {
        super(
            connection,
            (ch: Channel) => {
                return new Promise((resolve) => {
                    ch.assertQueue(settings.queue.name, settings.queue.options)
                        .then(() => {
                            resolve();
                        });
                });
            },
            processMessageFn,
        );
    }

    public start(): void {
        this.consume(this.settings.queue.name, {})
            .then(() => {
                // consumption started
            });
    }

}

export default StreamConsumer;
