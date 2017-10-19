import Headers from "../../message/Headers";
import JobMessage from "../../message/JobMessage";
import HttpWorker, {IHttpWorkerSettings} from "./HttpWorker";

export interface IHttpXmlParserWorkerSettings extends IHttpWorkerSettings {
    parser_settings: any;
}

/**
 * This http-type worker communicates with xml parser over http connection
 *
 * It prepares the payload data and headers in format the xml parser requires
 */
class HttpXmlParserWorker extends HttpWorker {

    /**
     *
     * @param {IHttpXmlParserWorkerSettings} settings
     */
    constructor(protected settings: IHttpXmlParserWorkerSettings) {
        super(settings);
    }

    /**
     * Creates http request body to be sent
     *
     * @param {JobMessage} inMsg
     * @return {string}
     */
    public getHttpRequestBody(inMsg: JobMessage): string {
        return `{"data":${JSON.stringify(inMsg.getContent())},"settings":{}}`;
    }

    /**
     *
     * @param {JobMessage} inMsg
     * @return {Headers}
     */
    public getHttpRequestHeaders(inMsg: JobMessage): Headers {
        const httpHeaders = super.getHttpRequestHeaders(inMsg);
        httpHeaders.setHeader(Headers.CONTENT_TYPE, "application/json");

        return httpHeaders;
    }

}

export default HttpXmlParserWorker;
