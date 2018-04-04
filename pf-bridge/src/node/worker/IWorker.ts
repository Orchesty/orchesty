import JobMessage from "../../message/JobMessage";

export type WorkerProcessFn = (msg: JobMessage) => Promise<JobMessage[]>;
export type WorkerServiceFn = (msg: JobMessage) => Promise<JobMessage>;
export type WorkerReadyFn = () => Promise<boolean>;

interface IWorker {

    processData: WorkerProcessFn;

    processService: WorkerServiceFn;

    isWorkerReady: WorkerReadyFn;

}

export default IWorker;
