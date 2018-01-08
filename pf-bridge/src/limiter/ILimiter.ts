import JobMessage from "../message/JobMessage";

export default interface ILimiter {

    /**
     * Returns whether the limiter service is operable or not
     * Should return false if remote limiter does not respond
     *
     * @return {Promise<boolean>}
     */
    isReady(): Promise<boolean>;

    /**
     * Returns whether the message can be processed or not at the current time
     *
     * @param {JobMessage} msg
     * @return {Promise<boolean>}
     */
    canBeProcessed(msg: JobMessage): Promise<boolean>;

    /**
     * Postopones the message to be processed later
     *
     * @param {JobMessage} msg
     * @return {Promise<void>}
     */
    postpone(msg: JobMessage): Promise<void>;

}
