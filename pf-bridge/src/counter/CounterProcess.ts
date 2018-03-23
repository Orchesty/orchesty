import {default as CounterMessage} from "../message/CounterMessage";
import {ResultCode, ResultCodeGroup} from "../message/ResultCode";

interface ICounterLog {
    resultCode: ResultCode;
    node: string;
    message: string;
}

export interface ICounterProcessInfo {
    topology: string;
    correlation_id: string;
    process_id: string;
    parent_id: string;
    total: number;
    ok: number;
    nok: number;
    success: boolean;
    messages: ICounterLog[];
    start_timestamp: number;
    end_timestamp: number;
}

const ID_DELIMITER = ".";

class CounterProcess {

    /**
     * Returns top parent of job
     * @param {string} id
     * @return {string}
     * @private
     */
    public static getMostTopProcessId(id: string) {
        const stringId = `${id}`;
        const parts = stringId.split(ID_DELIMITER, 1);

        return parts[0];
    }

    /**
     *
     * @param {string} topology
     * @param {CounterMessage} cm
     * @return {ICounterProcessInfo}
     */
    public static createProcessInfo(topology: string, cm: CounterMessage): ICounterProcessInfo {
        return {
            topology,
            correlation_id: cm.getCorrelationId(),
            process_id: cm.getProcessId(),
            parent_id: cm.getParentId(),
            total: 1,
            ok: 0,
            nok: 0,
            success: true,
            messages: [],
            start_timestamp: Date.now(),
            end_timestamp: 0,
        };
    }

    /**
     *
     * @param {ICounterProcessInfo} processInfo
     * @param {CounterMessage} cm
     * @return {ICounterProcessInfo}
     */
    public static updateProcessInfo(processInfo: ICounterProcessInfo, cm: CounterMessage): ICounterProcessInfo {
        if (cm.getResultCode() === ResultCode.SUCCESS ||
            cm.getResultGroup() === ResultCodeGroup.NON_STANDARD
        ) {
            processInfo.ok += 1;
        } else {
            processInfo.nok += 1;
            processInfo.success = false;
        }

        processInfo.total = processInfo.total + (cm.getMultiplier() * cm.getFollowing());

        const log: ICounterLog = {node: cm.getNodeId(), resultCode: cm.getResultCode(), message: cm.getResultMsg()};
        processInfo.messages.push(log);

        return processInfo;
    }

    /**
     * Returns true if process is completely finished
     *
     * @param {ICounterProcessInfo} job
     * @return {boolean}
     * @private
     */
    public static isProcessFinished(job: ICounterProcessInfo) {
        if (job.nok + job.ok === job.total) {
            return true;
        }
        return false;
    }

}

export default CounterProcess;
