import Node from "../node/Node";
import TopologyReadinessProbe, {Status} from "./TopologyReadinessProbe";
import TopologyStatusSwitch from "./TopologyStatusSwitch";

const RETRY_TIME = 10000; // 10s

class TopologyActivator {

    private isRunning: boolean = false;

    constructor(
        private probe: TopologyReadinessProbe,
        private switcher: TopologyStatusSwitch,
        private nodes: Node[],
    ) {}

    public run() {
        if (this.isRunning) {
            return;
        }

        this.probe.checkTopology()
            .then((res: {status: Status, message: string}) => {
                if (res.status === Status.SUCCESS) {
                    this.activate();
                } else {
                    // Try to activate later if not ready yet
                    setTimeout(() => { this.run(); }, RETRY_TIME);
                }
            });
    }

    /**
     * Opens all initial nodes via http request and switches topology status
     */
    private activate() {
        this.nodes.forEach((node: Node) => {
            // log
        });
    }

}

export default TopologyActivator;
