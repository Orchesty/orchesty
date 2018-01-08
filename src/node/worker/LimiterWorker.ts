import ILimiter from "../../limiter/ILimiter";
import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

export default class LimiterWorker implements IWorker {

    /**
     *
     * @param {ILimiter} limiter
     * @param {IWorker} worker
     */
    public constructor(
        private limiter: ILimiter,
        private worker: IWorker,
    ) {}

    /**
     * Checks against limiter if the given message can be processed now.
     * If not it postpones this message using limiter.
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage[]>}
     */
    public async processData(msg: JobMessage): Promise<JobMessage[]> {
        const can = await this.limiter.canBeProcessed(msg);

        if (!can) {
            this.postpone(msg);

            return [];
        }

        const all = await this.worker.processData(msg);
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

    /**
     *
     * @return {Promise<boolean>}
     */
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
    private postpone(msg: JobMessage): void {
        try {
            this.limiter.postpone(msg);
        } catch (e) {
            logger.error("Worker[type='limiter'] cannot postpone message.", {error: e});
        }
    }

}
