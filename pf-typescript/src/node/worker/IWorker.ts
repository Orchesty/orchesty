import JobMessage from "../../message/JobMessage";

export type WorkerProcessFn = (msg: JobMessage) => Promise<JobMessage[]>;

interface IWorker {

    processData: WorkerProcessFn;

}

export default IWorker;
