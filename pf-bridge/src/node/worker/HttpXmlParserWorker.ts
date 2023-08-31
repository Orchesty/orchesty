import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
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
     * @param {IMetrics} metrics
     */
    constructor(protected settings: IHttpXmlParserWorkerSettings, protected metrics: IMetrics) {
        super(settings, metrics);
    }

    /**
     * Creates http request body to be sent
     *
     * @param {JobMessage} inMsg
     * @return {string}
     */
    public getHttpRequestBody(inMsg: JobMessage): string {
        if (inMsg.getHeaders().getHeader(Headers.CONTENT_TYPE) === "application/json") {
            return this.getContent(inMsg.getContent());
        } else {
            return this.getContent(JSON.stringify(inMsg.getContent()));
        }
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

    /**
     * @param {string} data
     * @returns {string}
     */
    private getContent(data: string): string {
        let setStr = "{}";
        if (this.settings && this.settings.parser_settings) {
            setStr = JSON.stringify(this.settings.parser_settings);
        }

        return `{"data":${data ? data : ""},"settings":${setStr}}`;
    }

}

export default HttpXmlParserWorker;
