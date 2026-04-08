import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import { NAME as TOPOLOGY_FAILED_MAPPER } from './TopologyFailedEmailMapper';
import { NAME as TOPOLOGY_FAILED_MESSAGE_MAPPER } from './TopologyFailedMessageEmailMapper';
import { NAME as TOPOLOGY_FAILED_REPEATEDLY_MAPPER } from './TopologyFailedRepeatedlyEmailMapper';
import { NAME as TOPOLOGY_SLOW_MAPPER } from './TopologySlowEmailMapper';

export const NAME = 'notification-router';

const PRESET_TO_FOLLOWER: Record<string, string> = {
    topology_failed: TOPOLOGY_FAILED_MAPPER,
    topology_failed_repeatedly: TOPOLOGY_FAILED_REPEATEDLY_MAPPER,
    topology_failed_message: TOPOLOGY_FAILED_MESSAGE_MAPPER,
    topology_slow: TOPOLOGY_SLOW_MAPPER,
};

export default class NotificationRouter extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<{ preset_id: string }>): ProcessDto {
        const { preset_id } = dto.getJsonData();
        const follower = PRESET_TO_FOLLOWER[preset_id];

        if (!follower) {
            return dto.setStopProcess(
                ResultCode.DO_NOT_CONTINUE,
                `No mapper configured for preset ${preset_id}`,
            );
        }

        return dto.setForceFollowers(follower);
    }

}
