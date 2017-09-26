import JobMessage from "../../message/JobMessage";

export type ForwardSingleSplitFn = (msg: JobMessage) => Promise<void>;

interface IPartialForwarder {

    forwardSingleSplit: ForwardSingleSplitFn;

}

export default IPartialForwarder;
