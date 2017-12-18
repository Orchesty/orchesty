import { Channel, Message } from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Consumer} from "amqplib-plus/dist/lib/Consumer";

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
        channel.ack(msg);
        await this.processData(msg);
    }

}

export default CounterConsumer;
