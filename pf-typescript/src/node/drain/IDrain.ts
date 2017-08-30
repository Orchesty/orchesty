import JobMessage from "../../message/JobMessage";

export type DrainOpenFn = (msg: JobMessage) => Promise<void>;

interface IDrain {

    open: DrainOpenFn;

    getMessageBuffer(message: JobMessage): JobMessage[];

}

export default IDrain;
