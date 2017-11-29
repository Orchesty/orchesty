import {TimeUtils} from "hb-utils/dist/lib/TimeUtils";

export class Measurement {

    /** When the amqp message was originally published */
    private published: number = 0;
    /** When the amqp message was consumed */
    private received: number = 0;
    /** When bridge worker started to process message */
    private workerStart: number = 0;
    /** When bridge worker finished processing message */
    private workerEnd: number = 0;
    /** When bridge drain finished forwarding */
    private finished: number = 0;

    /**
     *
     * @param {number} timestamp
     */
    public setPublished(timestamp: number) {
        if (!timestamp || timestamp < 0) {
            timestamp = TimeUtils.nowMili();
        }

        this.published = timestamp;
    }

    /**
     *
     */
    public markReceived() {
        this.received = TimeUtils.nowMili();
    }

    /**
     *
     */
    public markWorkerStart() {
        this.workerStart = TimeUtils.nowMili();
    }

    /**
     *
     */
    public markWorkerEnd() {
        this.workerEnd = TimeUtils.nowMili();
    }

    /**
     *
     */
    public markFinished() {
        this.finished = TimeUtils.nowMili();
    }

    /**
     * Returns how long [ms] the message was stored in rabbit broker
     *
     * @return {number}
     */
    public getWaitingDuration(): number {
        if (this.received <= this.published) {
            return 0;
        }

        return this.received - this.published;
    }

    /**
     * Returns how long [ms] took the worker to process the message
     *
     * @return {number}
     */
    public getWorkerDuration(): number {
        if (this.workerEnd <= this.workerStart) {
            return 0;
        }

        return this.workerEnd - this.workerStart;
    }

    /**
     * Returns how long [ms] took the node to process the message since consumption till the end in this node
     *
     * @return {number}
     */
    public getNodeDuration(): number {
        if (this.finished <= this.received) {
            return 0;
        }

        return this.finished - this.received;
    }

    /**
     *
     * @param {number} timestamp
     */
    public setReceived(timestamp: number) {
        if (!timestamp || timestamp < 0) {
            timestamp = TimeUtils.nowMili();
        }

        this.received = timestamp;
    }

    /**
     *
     * @param {number} timestamp
     */
    public setWorkerStart(timestamp: number) {
        if (!timestamp || timestamp < 0) {
            timestamp = TimeUtils.nowMili();
        }

        this.workerStart = timestamp;
    }

    /**
     *
     * @param {number} timestamp
     */
    public setWorkerEnd(timestamp: number) {
        if (!timestamp || timestamp < 0) {
            timestamp = TimeUtils.nowMili();
        }

        this.workerEnd = timestamp;
    }

    /**
     *
     * @return {number}
     */
    public getPublished(): number {
        return this.published;
    }

    /**
     *
     * @return {number}
     */
    public getReceived(): number {
        return this.received;
    }

    /**
     *
     * @return {number}
     */
    public getWorkerStart(): number {
        return this.workerStart;
    }

}
