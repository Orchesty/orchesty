import JobMessage from "../../message/JobMessage";
import HttpWorker, {IHttpWorkerSettings} from "./HttpWorker";

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

}

export default HttpXmlParserWorker;
