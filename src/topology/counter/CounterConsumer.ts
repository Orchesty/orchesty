import { Channel, Message } from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Consumer} from "amqplib-plus/dist/lib/Consumer";
import logger from "../../logger/Logger";

class CounterConsumer extends Consumer {

    private processData: (msg: Message) => void;

    constructor(
        conn: Connection,
        channelCb: (ch: Channel) => Promise<any>,
        processData: (msg: Message) => void,
    ) {
        super(conn, channelCb);
        this.processData = processData;
    }

    public async processMessage(msg: Message, channel: Channel): Promise<void> {
        try {
            await this.processData(msg);
            channel.ack(msg);
        } catch (e) {
            logger.error("Counter consumer error.", {error: e});
            channel.ack(msg);
        }
    }

}

export default CounterConsumer;
