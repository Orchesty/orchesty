import JobMessage from "../../../message/JobMessage";

export interface IHttpWorkerRequestParams {
    method: string;
    url: string;
    json: any;
    gzip?: boolean;
    body?: string;
    headers: {
        job_id: string,
        sequence_id: number,
        message_id: string,
        reply_to_url?: string,
        reply_to_method?: string,
    };
}

class AHttpWorker {

    constructor(private method: string, private url: string) {}

    protected getHttpRequestParams(inMsg: JobMessage): IHttpWorkerRequestParams {
        return {
            method: this.method.toUpperCase(),
            url: this.url,
            json: JSON.parse(inMsg.getContent()),
            headers: {
                job_id: inMsg.getJobId(),
                sequence_id: inMsg.getSequenceId(),
                message_id: inMsg.getUuid(),
            },
        };
    }

}

export default AHttpWorker;
