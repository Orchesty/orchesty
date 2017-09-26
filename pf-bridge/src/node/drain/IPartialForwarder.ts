import JobMessage from "../../message/JobMessage";

export type ForwardPartFn = (msg: JobMessage) => Promise<void>;

interface IPartialForwarder {

    forwardPart: ForwardPartFn;

}

export default IPartialForwarder;
