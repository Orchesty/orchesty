import JobMessage from "../../../message/JobMessage";

export interface IHttpWorkerRequestParams {
    method: string;
    url: string;
    json: boolean;
    gzip: boolean;
    body: string;
    headers: {
        messageId: string,
        replyToUrl?: string,
        replyToMethod?: string,
    };
}

class AHttpWorker {

    constructor(private method: string, private url: string) {
    }

    protected getHttpRequestParams(inMsg: JobMessage): IHttpWorkerRequestParams {
        return {
            method: this.method.toUpperCase(),
            url: this.url,
            json: true,
            gzip: true,
            body: inMsg.getContent(),
            headers: {
                messageId: inMsg.getId(),
            },
        };
    }

}

export default AHttpWorker;
