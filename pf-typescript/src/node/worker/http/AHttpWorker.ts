import JobMessage, {IResult} from "../../../message/JobMessage";

export interface IHttpWorkerRequestParams {
    method: string;
    url: string;
    json: boolean;
    gzip: boolean;
    body: string;
    headers: {
        job_id: string,
        sequence_id: number,
        message_id: string,
        reply_to_url?: string,
        reply_to_method?: string,
    };
}

class AHttpWorker {

    protected static createOutMessage(inMsg: JobMessage, content: string, result: IResult) {
        return new JobMessage(
            inMsg.getJobId(),
            inMsg.getSequenceId(),
            inMsg.getHeaders(),
            content,
            result,
        );
    }

    constructor(private method: string, private url: string) {}

    protected getHttpRequestParams(inMsg: JobMessage): IHttpWorkerRequestParams {
        return {
            method: this.method.toUpperCase(),
            url: this.url,
            json: true,
            gzip: true,
            body: JSON.stringify(inMsg.getContent()),
            headers: {
                job_id: inMsg.getJobId(),
                sequence_id: inMsg.getSequenceId(),
                message_id: inMsg.getUuid(),
            },
        };
    }

}

export default AHttpWorker;
