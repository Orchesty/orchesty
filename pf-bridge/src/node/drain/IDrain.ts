import JobMessage from "../../message/JobMessage";

export type DrainForwardFn = (msg: JobMessage) => void;

interface IDrain {

    forward: DrainForwardFn;

}

export default IDrain;
