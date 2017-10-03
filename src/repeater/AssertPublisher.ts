import {Channel} from "amqplib";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";

class AssertionPublisher extends Publisher {

    /**
     * Asserts the queue and then send a message to it
     *
     * @param {string} queue
     * @param queueOpts
     * @param {Buffer} content
     * @param {{}} msgOpts
     * @return {Promise<void>}
     */
    public assertQueueAndSend(queue: string, queueOpts: any, content: Buffer, msgOpts: {}): Promise<void> {
        return this.assertQueue(queue, queueOpts)
            .then(() => {
                return this.sendToQueue(queue, content, msgOpts);
            });
    }

    /**
     *
     * @param {string} name
     * @param options
     * @return {Promise<Channel>}
     */
    private assertQueue(name: string, options: any) {
        return this.channel
            .then((ch: Channel) => {
                return ch.assertQueue(name, options);
            });
    }

}

export default AssertionPublisher;
