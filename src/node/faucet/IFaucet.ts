import JobMessage from "../../message/JobMessage";

export type FaucetProcessMsgFn = (msgIn: JobMessage) => Promise<void>;
export type FaucetOpenFn = (processMsgFn: FaucetProcessMsgFn) => Promise<void>;
export type FaucetStopFn = () => Promise<void>;

interface IFaucet {

    open: FaucetOpenFn;

    stop: FaucetStopFn;

}

export default IFaucet;
