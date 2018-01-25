import {INodeConfig} from "./Configurator";

interface INodeConfigProvider {

    getNodeConfig(nodeId: string, isMulti: boolean): INodeConfig;

}

export default INodeConfigProvider;
