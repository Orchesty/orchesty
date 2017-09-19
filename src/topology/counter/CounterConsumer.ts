import { Channel, Message } from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Consumer from "lib-nodejs/dist/src/rabbitmq/Consumer";

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

    public processMessage(msg: Message, channel: Channel): void {
        channel.ack(msg);
        this.processData(msg);
    }

}

export default CounterConsumer;
