import {ICounterProcessInfo} from "../CounterProcess";

export default interface ICounterStorage {

    has(topology: string, processId: string): Promise<boolean>;
    hasSome(topology: string): Promise<boolean>;
    get(topology: string, processId: string): Promise<ICounterProcessInfo|null>;
    add(topology: string, info: ICounterProcessInfo): Promise<boolean>;
    remove(topology: string, processId: string): Promise<boolean>;

    stop(): Promise<void>;

}
