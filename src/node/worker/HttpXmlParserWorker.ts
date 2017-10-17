import Headers from "../../message/Headers";
import JobMessage from "../../message/JobMessage";
import HttpWorker, {IHttpWorkerRequestParams} from "./HttpWorker";

class HttpXmlParserWorker extends HttpWorker {

    /**
     *
     * @param {JobMessage} inMsg
     * @return {IHttpWorkerRequestParams}
     */
    protected getJobRequestParams(inMsg: JobMessage): IHttpWorkerRequestParams {

        const headersToSend = new Headers(inMsg.getHeaders().getRaw());
        headersToSend.setPFHeader(Headers.NODE_ID, this.settings.node_label.node_id);
        headersToSend.setPFHeader(Headers.NODE_NAME, this.settings.node_label.node_name);

        const contentWrap = {
            data: JSON.stringify(inMsg.getContent()),
        };

        return {
            method: this.settings.method.toUpperCase(),
            url: this.getUrl(this.settings.process_path),
            json: contentWrap,
            followAllRedirects: true,
            headers: headersToSend.getRaw(),
        };
    }

}

export default HttpXmlParserWorker;
