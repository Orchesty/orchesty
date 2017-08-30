import { Channel } from "amqplib";
import logger from "lib-nodejs/dist/src/logger/Logger";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import { DrainOpenFn } from "../drain/IDrain";
import { WorkerProcessFn } from "../worker/IWorker";
import Consumer from "./amqp/AMQPConsumer";
import IFaucet from "./IFaucet";

export interface IAMQPFaucetSettings {
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

class AMQPFaucet implements IFaucet {

    private settings: IAMQPFaucetSettings;
    private connection: Connection;
    private consumer: Consumer;

    /**
     * @param settings
     * @param connection
     */
    constructor(settings: any, connection: Connection) {
        this.settings = settings;
        this.connection = connection;
    }

    /**
     * Creates channel and starts messages consumption.
     */
    public open(processData: WorkerProcessFn, drain: DrainOpenFn): Promise<() => void> {

        logger.info("RabbitMQ Faucet being opened ...");

        const prepareFn = (ch: Channel) => {
            const s = this.settings;
            s.exchange.options["x-dead-letter-exchange"] = s.dead_letter_exchange.name;
            return Promise.all([
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
            ]).then((results: any) => {
                const ok = results[0];
                const ex = results[1];
                return ch.bindQueue(ok.queue, ex.exchange, s.routing_key);
            });
        };

        this.consumer = new Consumer(this.connection, prepareFn, processData, drain);

        return Promise.resolve(() => {
            this.consumer.consume(this.settings.queue.name, {})
                .then(() => {
                    logger.info(`RabbitMQ Faucet for queue "${this.settings.queue.name}" opened`);
                });
        });
    }

}

export default AMQPFaucet;
