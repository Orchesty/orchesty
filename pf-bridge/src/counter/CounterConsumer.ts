import { Channel, Message } from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Consumer} from "amqplib-plus/dist/lib/Consumer";
import {TimeUtils} from "hb-utils/dist/lib/TimeUtils";
import logger from "../logger/Logger";

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
        const start = TimeUtils.nowMili();
        try {
            await this.processData(msg);
            channel.ack(msg);
        } catch (e) {
            logger.error("Counter consumer error.", {error: e});
            channel.ack(msg);
        } finally {
            logger.info(`PROFILER - processMessage consume->ack duration ${TimeUtils.nowMili() - start}ms`);
        }
    }

}

export default CounterConsumer;
