import logger from "../logger/Logger";
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

    public static readonly START_SEQUENCE_ID = 0;

    private nodeId: string;
    private buffer: {[key: string]: IBufferType };
    private bufferTtl: number;

    /**
     * @param nodeId {string}
     * @param bufferTtl {Number} 86400000ms = 24h
     * TODO - implement sequence/storage size monitoring using metrics
     */
    constructor(nodeId: string, bufferTtl: number = 86400000) {
        this.nodeId = nodeId;
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
        const buf = this.getBuffer(msg.getProcessId());

        if (msg.getSequenceId() < buf.waitingFor) {
            let warn = `Resequencer has already processed sequenceId ${msg.getSequenceId()}."`;
            warn += " This is possible message duplicate and this message will be ignored.";
            logger.warn(warn, logger.ctxFromMsg(msg));
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
     * @param {string} processId
     * @return {JobMessage[]}
     */
    private getBuffer(processId: string): IBufferType {
        if (!this.buffer[processId]) {
            // Create blank buffer
            this.buffer[processId] = {
                messages: {},
                waitingFor: Resequencer.START_SEQUENCE_ID,
                timeout: setTimeout(() => { delete this.buffer[processId]; }, this.bufferTtl),
            };
        }

        return this.buffer[processId];
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
            buffer.timeout = setTimeout(() => { delete this.buffer[msg.getProcessId()]; }, this.bufferTtl);
        }

        return out;
    }

}

export default Resequencer;
