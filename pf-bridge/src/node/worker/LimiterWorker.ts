import ILimiter from "../../limiter/ILimiter";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import {IFaucetConfig} from "../../topology/Configurator";
import {IAmqpFaucetSettings} from "../faucet/AmqpFaucet";
import AWorker from "./AWorker";
import IWorker from "./IWorker";

export default class LimiterWorker extends AWorker {

    /**
     *
     * @param {ILimiter} limiter
     * @param {IWorker} worker
     * @param faucetConfig
     */
    public constructor(
        private limiter: ILimiter,
        private worker: IWorker,
        private faucetConfig: IFaucetConfig,
    ) {
        super();
    }

    /**
     * Checks against limiter if the given message can be processed now.
     * If not it postpones this message using limiter.
     *
     * @inheritdoc
     */
    public async processData(msg: JobMessage): Promise<JobMessage[]> {
        // add special header with next nods
        if (this.additionalHeaders !== undefined) {
            this.additionalHeaders.forEach((value: string, key: string) => {
                msg.getHeaders().setPFHeader(key, value);
            });
        }

        const can = await this.limiter.canBeProcessed(msg);

        if (!can) {
            logger.debug(`LimitedWorker: cant process`);
            await this.postpone(msg);
            return [];
        }

        const all = await this.worker.processData(msg);

        logger.debug(`LimitedWorker: after process`);
        const processed: JobMessage[] = [];

        all.forEach((out: JobMessage) => {
            if (out.getResult().code === ResultCode.LIMIT_EXCEEDED) {
                this.postpone(out);
            } else {
                processed.push(out);
            }
        });

        return processed;
    }

    /** @inheritdoc */
    public async isWorkerReady(): Promise<boolean> {
        try {
            const [ l, w ] = await Promise.all([
                this.limiter.isReady(),
                this.worker.isWorkerReady(),
            ]);

            return (l && w);
        } catch (e) {
            return false;
        }
    }

    /**
     *
     * @param {JobMessage} msg
     */
    private async postpone(msg: JobMessage): Promise<void> {
        try {
            const faucet: IAmqpFaucetSettings = this.faucetConfig.settings;
            msg.getHeaders().setPFHeader(Headers.LIMIT_RETURN_EXCHANGE, faucet.exchange.name);
            msg.getHeaders().setPFHeader(Headers.LIMIT_RETURN_ROUTING_KEY, faucet.routing_key);
            msg.getHeaders().setPFHeader(Headers.LIMIT_MESSAGE_FROM_LIMITER, 'true');

            await this.limiter.postpone(msg);
        } catch (e) {
            logger.error("Worker[type='limiter'] cannot postpone message.", {error: e});
        }
    }

}
