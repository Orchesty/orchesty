import * as request from "request";
import {UrlOptions} from "request";
import logger from "../../logger/Logger";
import {ITopologyConfig} from "../Configurator";

const REQUEST_TIMEOUT = 5000;

class MultiProbeConnector {

    private url: string;

    /**
     *
     * @param {string} multiProbeHost
     * @param {number} multiProbePort
     */
    constructor(
        private multiProbeHost: string = "multi-probe",
        private multiProbePort: number = 8007,
    ) {
        this.url = `http://${this.multiProbeHost}:${this.multiProbePort}`;
    }

    /**
     * Sends request to add topology to multi probe
     */
    public addTopology(topology: ITopologyConfig) {
        const requestOptions = {
            method: "POST",
            url: `${this.url}/topology/add`,
            timeout: REQUEST_TIMEOUT,
            body: topology,
            headers: {
                "Content-Type": "application/json",
            },
        };

        this.send(requestOptions);
    }

    /**
     * Sends request to remove topology from multi probe
     */
    public removeTopology(topologyId: string) {
        const requestOptions = {
            method: "GET",
            url: `${this.url}/topology/remove?topologyId=${topologyId}`,
            timeout: REQUEST_TIMEOUT,
        };

        this.send(requestOptions);
    }

    private send(options: UrlOptions) {
        logger.info(`MultiProbeConnector sending request to: ${options.url}`);
        request(options, (err, response) => {
            if (err) {
                logger.error(`MultiProbeConnector request to ${options.url} ended with error: ${err.message}`);
                return;
            }

            if (response.statusCode !== 200) {
                const code = response.statusCode;
                logger.error(`MultiProbeConnector request to ${options.url} resulted with statusCode: ${code}`);
                return;
            }

            logger.info(`MultiProbeConnector request to: ${options.url} OK.`);
        });
    }

}

export default MultiProbeConnector;
