import CoreFormsEnum from '@orchesty/nodejs-sdk/dist/lib/Application/Base/CoreFormsEnum';
import AConnector from '@orchesty/nodejs-sdk/dist/lib/Connector/AConnector';
import logger from '@orchesty/nodejs-sdk/dist/lib/Logger/Logger';
import RequestDto from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/RequestDto';
import { HttpMethods } from '@orchesty/nodejs-sdk/dist/lib/Transport/HttpMethods';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { checkParams } from '@orchesty/nodejs-sdk/dist/lib/Utils/Validations';
import { CLOUD_API_KEY, CLOUD_URL } from '../CloudCallbackApplication';

export const NAME = 'cloud-callback-connector';

/**
 * Sends a POST callback to the Cloud backend confirming the invoice
 * was created in iDoklad.
 *
 * Input:  { invoiceId, iDokladInvoiceId }
 * Output: Cloud response body
 */
export default class CloudCallbackConnector extends AConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const data = dto.getJsonData() as Record<string, unknown>;
        checkParams(data, ['invoiceId', 'iDokladInvoiceId']);

        const applicationInstall = await this.getApplicationInstallFromProcess(dto);
        const settings = applicationInstall.getSettings()?.[CoreFormsEnum.AUTHORIZATION_FORM];
        const baseUrl = (settings?.[CLOUD_URL] as string).replace(/\/+$/, '');
        const apiKey = settings?.[CLOUD_API_KEY] as string;

        const callbackUrl = `${baseUrl}/webhooks/idoklad`;
        const bodyStr = JSON.stringify({
            invoiceId: data.invoiceId,
            iDokladInvoiceId: data.iDokladInvoiceId,
        });

        const request = new RequestDto(
            callbackUrl,
            HttpMethods.POST,
            dto,
            bodyStr,
            {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                // eslint-disable-next-line @typescript-eslint/naming-convention
                'X-Api-Key': apiKey,
            },
        );

        logger.info(
            `[DEBUG] CloudCallback URL: ${request.getUrl()}, method: ${request.getMethod()}, headers: ${JSON.stringify(request.getHeaders())}, body: ${request.getBody()}`,
            dto,
            true,
        );

        const response = await this.getSender().send(request, {
            success: [200],
            stopAndFail: '<500',
            repeat: '>=500',
        }, 60, 3);
        dto.setData(response.getBody());

        return dto;
    }

}
