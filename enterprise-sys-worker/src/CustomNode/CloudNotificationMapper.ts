import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export const NAME = 'cloud-notification-mapper';

/* eslint-disable @typescript-eslint/naming-convention */
interface IInput {
    preset_id: string;
    tenant_id: string;
    channel: string;
    event: {
        event_type: string;
        occurred_at: string;
        severity: string;
        message?: string;
        topology?: { id: string; name: string };
        node?: { id: string; name: string };
    };
    recipients: string[] | null;
}

interface IOutput {
    instanceId: string;
    instanceSecret: string;
    notification: {
        event_type: string;
        severity: string;
        message: string;
        topology_id?: string;
        topology_name?: string;
        node_name?: string;
        occurred_at: string;
    };
}
/* eslint-enable @typescript-eslint/naming-convention */

export default class CloudNotificationMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
        const { event } = dto.getJsonData();

        const instanceId = process.env.ORCHESTY_CLOUD_INSTANCE_ID ?? '';
        const instanceSecret = process.env.ORCHESTY_CLOUD_INSTANCE_SECRET ?? '';

        return dto.setNewJsonData<IOutput>({
            instanceId,
            instanceSecret,
            /* eslint-disable @typescript-eslint/naming-convention */
            notification: {
                event_type: event.event_type,
                severity: event.severity,
                message: event.message ?? '',
                topology_id: event.topology?.id,
                topology_name: event.topology?.name,
                node_name: event.node?.name,
                occurred_at: event.occurred_at ?? new Date().toISOString(),
            },
        });
    }

}
