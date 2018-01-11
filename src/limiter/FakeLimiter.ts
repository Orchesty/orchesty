import JobMessage from "../message/JobMessage";
import ILimiter from "./ILimiter";
import Headers from "../message/Headers";

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
        // If limit headers are missing, allow processing it
        if (!msg.getHeaders().getPFHeader(Headers.LIMIT_KEY) ||
            !msg.getHeaders().getPFHeader(Headers.LIMIT_TIME) ||
            !msg.getHeaders().getPFHeader(Headers.LIMIT_VALUE)
        ) {
            return true;
        }

        // Here possibly contact the limiter and pass him the limit headers
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
