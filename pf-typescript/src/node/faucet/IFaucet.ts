import { DrainOpenFn } from "../drain/IDrain";
import { WorkerProcessFn } from "../worker/IWorker";

export type FaucetOpenFn = (processData: WorkerProcessFn, drain: DrainOpenFn) => Promise<() => void>;

interface IFaucet {

    open: FaucetOpenFn;

}

export default IFaucet;
