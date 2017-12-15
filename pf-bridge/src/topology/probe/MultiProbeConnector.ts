import {ITopologyConfig} from "../Configurator";
import RequestSender from "../util/RequestSender";

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
            body: JSON.stringify(topology),
            headers: {
                "Content-Type": "application/json",
            },
        };

        RequestSender.send(requestOptions);
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

        RequestSender.send(requestOptions);
    }

}

export default MultiProbeConnector;
