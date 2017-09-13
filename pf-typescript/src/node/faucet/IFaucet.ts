import JobMessage from "../../message/JobMessage";

export type FaucetProcessMsgFn = (msgIn: JobMessage) => Promise<JobMessage>;
export type FaucetOpenFn = (processMsgFn: FaucetProcessMsgFn) => Promise<void>;

interface IFaucet {

    open: FaucetOpenFn;

}

export default IFaucet;
