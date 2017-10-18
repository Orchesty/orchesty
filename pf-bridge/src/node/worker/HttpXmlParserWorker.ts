import JobMessage from "../../message/JobMessage";
import HttpWorker, {IHttpWorkerSettings} from "./HttpWorker";

export interface IHttpXmlParserWorkerSettings extends IHttpWorkerSettings {
    parser_settings: any;
}

class HttpXmlParserWorker extends HttpWorker {

    constructor(protected settings: IHttpXmlParserWorkerSettings) {
        super(settings);
    }

    /**
     * Creates http request body to be sent
     *
     * @param {JobMessage} inMsg
     * @return {string}
     */
    protected getHttpRequestBody(inMsg: JobMessage): string {
        return JSON.stringify({
            data: inMsg.getContent(),
            settings: this.settings.parser_settings,
        });
    }

}

export default HttpXmlParserWorker;
