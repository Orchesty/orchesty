import { NAME as BEECEPTOR_APP_NAME } from '@orchesty/connector-beeceptor/dist/BeeceptorApplication';
import Webhook from '@orchesty/nodejs-sdk/dist/lib/Application/Database/Webhook';
import WebhookRepository from '@orchesty/nodejs-sdk/dist/lib/Application/Database/WebhookRepository';
import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import TopologyRunner from '@orchesty/nodejs-sdk/dist/lib/Topology/TopologyRunner';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import crypto from 'crypto';
import BeeceptorApplication from '../BeeceptorApplication';

export const NAME = `${BEECEPTOR_APP_NAME}-create-webhooks`;

export default class BeeceptorCreateWebhooks extends ABatchNode {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: BatchProcessDto): Promise<BatchProcessDto> {
        const app = this.getApplication<BeeceptorApplication>();
        const subscriptions = app.getWebhookSubscriptions();

        if (!subscriptions.length) {
            return dto.removeBatchCursor();
        }

        const appInstall = await this.getApplicationInstallFromProcess(dto);
        const repository = this.getDbClient().getRepository(Webhook) as WebhookRepository;

        const webhooks = await repository.findMany({
            users: [appInstall.getUser()],
            apps: [appInstall.getName()],
            sdks: [appInstall.getSdk()],
        });

        const unsubscribed = subscriptions.find(
            (subscription) => !webhooks.some(
                (webhook) => webhook.getName() === subscription.getName(),
            ),
        );

        if (!unsubscribed) {
            return dto;
        }

        const token = crypto.randomBytes(64).toString('hex');
        const request = app.getRequestDto(
            dto,
            appInstall,
            HttpMethods.POST,
            '/api/webhooks',
            {
                event: unsubscribed.getName(),
                url: TopologyRunner.getWebhookUrl(
                    unsubscribed.getTopology(),
                    unsubscribed.getNode(),
                    token,
                ),
            },
        );

        const { id } = (
            await this.getSender().send<IResponse>(request)
        ).getJsonBody();

        await repository.insert(
            new Webhook()
                .setWebhookId(id)
                .setUser(appInstall.getUser())
                .setNode(unsubscribed.getNode())
                .setToken(token)
                .setApplication(appInstall.getName())
                .setTopology(unsubscribed.getTopology())
                .setName(unsubscribed.getName())
                .setSdk(appInstall.getSdk()),
        );

        return dto.setBatchCursor('1', true);
    }

}

export interface IResponse {
    id: string;
}
