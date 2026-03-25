import BeeceptorApplicationBase from '@orchesty/connector-beeceptor/dist//BeeceptorApplication';
import WebhookSubscription from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Webhook/WebhookSubscription';
import TopologyRunner from '@orchesty/nodejs-sdk/dist/lib/Topology/TopologyRunner';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';

export enum Topology {
    MYSQL_TO_BEECEPTOR_CATEGORY = 'mysql-to-beeceptor-category',
    MYSQL_TO_BEECEPTOR_PRODUCT = 'mysql-to-beeceptor-product',
    MYSQL_TO_BEECEPTOR_WEBHOOKS = 'mysql-to-beeceptor-webhooks',
}

export enum WebhookType {
    PRODUCT_CREATED = 'product:created',
    PRODUCT_UPDATED = 'product:updated',
    CATEGORY_CREATED = 'category:created',
    CATEGORY_UPDATED = 'category:updated',
    PRODUCT_CATEGORY_UPDATED = 'productCategory:all',
}

export default class BeeceptorApplication extends BeeceptorApplicationBase {

    public constructor(private readonly runner: TopologyRunner) {
        super();
    }

    public getWebhookSubscriptions(): WebhookSubscription[] {
        return [
            new WebhookSubscription(
                WebhookType.CATEGORY_CREATED,
                'category-created',
                Topology.MYSQL_TO_BEECEPTOR_CATEGORY,
            ),
            new WebhookSubscription(
                WebhookType.CATEGORY_UPDATED,
                'category-updated',
                Topology.MYSQL_TO_BEECEPTOR_CATEGORY,
            ),
            new WebhookSubscription(
                WebhookType.PRODUCT_CREATED,
                'product-created',
                Topology.MYSQL_TO_BEECEPTOR_PRODUCT,
            ),
            new WebhookSubscription(
                WebhookType.PRODUCT_UPDATED,
                'product-updated',
                Topology.MYSQL_TO_BEECEPTOR_PRODUCT,
            ),
            new WebhookSubscription(
                WebhookType.PRODUCT_CATEGORY_UPDATED,
                'product-category-updated',
                Topology.MYSQL_TO_BEECEPTOR_PRODUCT,
            ),
        ];
    }

    // eslint-disable-next-line @typescript-eslint/no-misused-promises, @typescript-eslint/strict-void-return
    public async syncAfterEnableCallback(req: Request): Promise<void> {
        const { user, sdk } = JSON.parse(String(req.body));

        await this.runner.runByName(
            {},
            Topology.MYSQL_TO_BEECEPTOR_WEBHOOKS,
            'subscribe',
            ProcessDto.createForFormRequest(this.getName(), user, sdk, crypto.randomUUID()),
            user,
        );
    }

    // eslint-disable-next-line @typescript-eslint/no-misused-promises, @typescript-eslint/strict-void-return
    public async syncAfterDisableCallback(req: Request): Promise<void> {
        const { user, sdk } = JSON.parse(String(req.body));

        await this.runner.runByName(
            {},
            Topology.MYSQL_TO_BEECEPTOR_WEBHOOKS,
            'unsubscribe',
            ProcessDto.createForFormRequest(this.getName(), user, sdk, crypto.randomUUID()),
            user,
        );
    }

    // eslint-disable-next-line @typescript-eslint/no-misused-promises, @typescript-eslint/strict-void-return
    public async syncAfterUninstallCallback(req: Request): Promise<void> {
        await this.syncAfterDisableCallback(req);
    }

}
