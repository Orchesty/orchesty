import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../message/JobMessage";

interface IBufferType {
    messages: {[key: number]: JobMessage };
    waitingFor: number;
    timeout: any;
}

/**
 * Resequencer is responsible for outputting messages ordered by their sequence_id
 */
class Resequencer {

    private buffer: {[key: string]: IBufferType };
    private bufferTtl: number;

    /**
     * @param bufferTtl {Number} 86400000ms = 24h
     * TODO - implement sequence/storage size monitoring using metrics
     */
    constructor(bufferTtl: number = 86400000) {
        this.buffer = {};
        this.bufferTtl = bufferTtl;
    }

    /**
     * Adds message to buffer and returns the sequence of messages stored in buffer
     *
     * @param {JobMessage} msg
     * @return JobMessage[]
     */
    public getMessages(msg: JobMessage): JobMessage[] {
        const buf = this.getBuffer(msg.getJobId());

        if (msg.getSequenceId() < buf.waitingFor) {
            let warn = `Resequencer has already processed seqId ${msg.getSequenceId()} of job "${msg.getJobId()}."`;
            warn += " This is possible message duplicate and will be ignored.";
            logger.warn(warn);
            return [];
        }

        buf.messages[msg.getSequenceId()] = msg;

        if (msg.getSequenceId() > buf.waitingFor) {
            return [];
        }

        return this.getSequencedMessages(buf, msg);
    }

    /**
     * Returns existing buffer or creates new one
     *
     * @param {string} jobId
     * @return {JobMessage[]}
     */
    private getBuffer(jobId: string): IBufferType {
        if (!this.buffer[jobId]) {
            this.buffer[jobId] = {
                messages: {},
                waitingFor: 1,
                timeout: setTimeout(() => { delete this.buffer[jobId]; }, this.bufferTtl),
            };
        }

        return this.buffer[jobId];
    }

    /**
     *
     * @param buffer
     * @param msg
     * @return JobMessage[]
     */
    private getSequencedMessages(buffer: IBufferType, msg: JobMessage): JobMessage[] {
        const out: JobMessage[] = [];

        while (true) {
            const desired = buffer.messages[buffer.waitingFor];
            if (!desired) {
                break;
            }

            out.push(desired);
            delete buffer.messages[buffer.waitingFor];
            buffer.waitingFor += 1;

            clearTimeout(buffer.timeout);
            buffer.timeout = setTimeout(() => { delete this.buffer[msg.getJobId()]; }, this.bufferTtl);
        }

        return out;
    }

}

export default Resequencer;
