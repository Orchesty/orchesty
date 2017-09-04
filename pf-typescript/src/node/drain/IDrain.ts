import JobMessage from "../../message/JobMessage";

export type DrainOpenFn = (msg: JobMessage) => Promise<boolean>;

interface IDrain {

    open: DrainOpenFn;

    getMessageBuffer(message: JobMessage): JobMessage[];

}

export default IDrain;
