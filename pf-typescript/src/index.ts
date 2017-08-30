import Pipes from "./Pipes";
import { exampleTopo } from "./topology";

const pipes = new Pipes(exampleTopo);

// pipes.startProbe(8000);
// pipes.startCounter();

pipes.startNode("node_1");
