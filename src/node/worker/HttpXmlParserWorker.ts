import JobMessage from "../../message/JobMessage";
import HttpWorker, {IHttpWorkerSettings} from "./HttpWorker";
import Headers from "../../message/Headers";

export interface IHttpXmlParserWorkerSettings extends IHttpWorkerSettings {
    parser_settings: any;
}

class HttpXmlParserWorker extends HttpWorker {

    private static readonly DATA_PLACEHOLDER = "[PF_XMLDATA_PLACEHOLDER]";

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
        const body: string = JSON.stringify({
            data: HttpXmlParserWorker.DATA_PLACEHOLDER,
            settings: this.settings.parser_settings,
        });

        return body.replace(HttpXmlParserWorker.DATA_PLACEHOLDER, inMsg.getContent());
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
