import WflowGetOrganizationsConnector from '@orchesty/connector-wflow/dist/Connector/WflowGetOrganizationsConnector';
import WflowApplicationBase, { WebhookType } from '@orchesty/connector-wflow/dist/WflowApplication';
import WebhookSubscription from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Webhook/WebhookSubscription';
import { OAuth2Provider } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Provider/OAuth2/OAuth2Provider';
import TopologyRunner from '@orchesty/nodejs-sdk/dist/lib/Topology/TopologyRunner';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { Topology } from './Enum/Topology';

export default class WflowApplication extends WflowApplicationBase {

    public constructor(
        provider: OAuth2Provider,
        wflowGetOrganizationsConnector: WflowGetOrganizationsConnector,
        private readonly runner: TopologyRunner,
    ) {
        super(provider, wflowGetOrganizationsConnector);
    }

    // eslint-disable-next-line @typescript-eslint/no-misused-promises, @typescript-eslint/strict-void-return
    public async syncAfterUninstallCallback(req: Request): Promise<void> {
        await this.syncAfterDisableCallback(req);
    }

    // eslint-disable-next-line @typescript-eslint/no-misused-promises, @typescript-eslint/strict-void-return
    public async syncAfterEnableCallback(req: Request): Promise<void> {
        const { user } = JSON.parse(String(req.body));

        await this.runner.runByName(
            {},
            Topology.WFLOW_TO_FLEXIBEE_WEBHOOKS,
            'subscribe',
            ProcessDto.createForFormRequest(this.getName(), user, crypto.randomUUID()),
            user,
        );
    }

    // eslint-disable-next-line @typescript-eslint/no-misused-promises, @typescript-eslint/strict-void-return
    public async syncAfterDisableCallback(req: Request): Promise<void> {
        const { user } = JSON.parse(String(req.body));

        await this.runner.runByName(
            {},
            Topology.WFLOW_TO_FLEXIBEE_WEBHOOKS,
            'unsubscribe',
            ProcessDto.createForFormRequest(this.getName(), user, crypto.randomUUID()),
            user,
        );
    }

    public getWebhookSubscriptions(): WebhookSubscription[] {
        return [
            new WebhookSubscription(WebhookType.DOCUMENT_READY_TO_EXPORT, 'document-ready-to-export', Topology.WFLOW_TO_FLEXIBEE_FAKTURA_PRIJATA),
        ];
    }

}
