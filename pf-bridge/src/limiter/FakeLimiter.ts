import JobMessage from "../message/JobMessage";
import ILimiter from "./ILimiter";

/**
 * Fake temp limiter
 */
export default class FakeLimiter implements ILimiter {

    /**
     * Local fake limiter always returns true
     *
     * @return {Promise<boolean>}
     */
    public async isReady(): Promise<boolean> {
        return true;
    }

    /**
     * Always returns true
     *
     * @param {JobMessage} msg
     * @return {Promise<boolean>}
     */
    public async canBeProcessed(msg: JobMessage): Promise<boolean> {
        return true;
    }

    /**
     * This should be never called
     *
     * @param {JobMessage} msg
     * @return {Promise<void>}
     */
    public async postpone(msg: JobMessage): Promise<void> {
        return;
    }

}
