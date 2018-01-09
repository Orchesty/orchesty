import {Channel} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import logger from "../../logger/Logger";
import {INodeLabel} from "../../topology/Configurator";
import Consumer from "./amqp/AMQPConsumer";
import IFaucet, {FaucetProcessMsgFn} from "./IFaucet";

export interface IAmqpFaucetSettings {
    node_label: INodeLabel;
    exchange: {
        name: string,
        type: string,
        options: any,
    };
    queue: {
        name: string,
        options: any,
    };
    prefetch: number;
    dead_letter_exchange: {
        name: string,
        type: string,
        options: any,
    };
    routing_key: string;
}

class AmqpFaucet implements IFaucet {

    private settings: IAmqpFaucetSettings;
    private connection: Connection;
    private consumer: Consumer;
    private consumerTag: string;

    /**
     * @param settings
     * @param connection
     */
    constructor(settings: IAmqpFaucetSettings, connection: Connection) {
        this.settings = settings;
        this.connection = connection;
    }

    /**
     * Creates channel and starts messages consumption.
     */
    public async open(processData: FaucetProcessMsgFn): Promise<void> {
        const s = this.settings;

        logger.info(`AmqpFaucet configured to consume "${s.queue.name}"`, { node_id: s.node_label.id});

        const prepareFn = async (ch: Channel) => {
            s.exchange.options["x-dead-letter-exchange"] = s.dead_letter_exchange.name;
            const results = await Promise.all([
                ch.assertQueue(s.queue.name, s.queue.options),
                ch.assertExchange(
                    s.exchange.name,
                    s.exchange.type,
                    s.exchange.options,
                ),
                ch.assertExchange(
                    s.dead_letter_exchange.name,
                    s.dead_letter_exchange.type,
                    s.dead_letter_exchange.options),
                ch.prefetch(s.prefetch),
            ]);

            const ok = results[0];
            const ex = results[1];

            return ch.bindQueue(ok.queue, ex.exchange, s.routing_key);
        };

        this.consumer = new Consumer(s.node_label, this.connection, prepareFn, processData);
        this.consumerTag = await this.consumer.consume(s.queue.name, {});

        logger.info(`AmqpFaucet started consuming "${s.queue.name}"`, { node_id: s.node_label.id });
    }

    /**
     * Stops messages consumption
     *
     * @return {Promise<void>}
     */
    public async stop(): Promise<void> {
        await this.consumer.cancel(this.consumerTag);
    }

}

export default AmqpFaucet;
