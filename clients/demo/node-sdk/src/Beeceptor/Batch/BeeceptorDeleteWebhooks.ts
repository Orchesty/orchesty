import Webhook from '@orchesty/nodejs-sdk/dist/lib/Application/Database/Webhook';
import WebhookRepository from '@orchesty/nodejs-sdk/dist/lib/Application/Database/WebhookRepository';
import ABatchNode from '@orchesty/nodejs-sdk/dist/lib/Batch/ABatchNode';
import OnRepeatException from '@orchesty/nodejs-sdk/dist/lib/Exception/OnRepeatException';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import BatchProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/BatchProcessDto';
import { StatusCodes } from 'http-status-codes';
import BeeceptorApplication from '../BeeceptorApplication';

export const NAME = 'beeceptor-delete-webhooks';

export default class BeeceptorDeleteWebhooks extends ABatchNode {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: BatchProcessDto): Promise<BatchProcessDto> {
        const app = this.getApplication<BeeceptorApplication>();
        const appInstall = await this.getApplicationInstallFromProcess(dto, null);
        const repository = this.getDbClient().getRepository(
            Webhook,
        ) as WebhookRepository;

        const webhook = await repository.findOne({
            users: [appInstall.getUser()],
            apps: [appInstall.getName()],
            sdks: [appInstall.getSdk()],
        });

        if (!webhook) {
            return dto;
        }

        const request = app.getRequestDto(
            dto,
            appInstall,
            HttpMethods.DELETE,
            `/api/webhooks/${webhook.getWebhookId()}`,
        );

        const response = await this.getSender().send(request);

        if (response.getResponseCode() !== StatusCodes.NO_CONTENT) {
            await repository.update(webhook.setUnsubscribeFailed(true));
            throw new OnRepeatException(300, 12, response.getBody());
        }

        await repository.remove(webhook);
        return dto.setBatchCursor('1', true);
    }

}
