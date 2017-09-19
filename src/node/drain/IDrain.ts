import JobMessage from "../../message/JobMessage";

export type DrainForwardFn = (msg: JobMessage) => Promise<JobMessage>;

interface IDrain {

    forward: DrainForwardFn;

    getMessageBuffer(message: JobMessage): JobMessage[];

}

export default IDrain;
