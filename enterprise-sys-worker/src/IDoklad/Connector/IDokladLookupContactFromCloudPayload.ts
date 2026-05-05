import logger from '@orchesty/nodejs-sdk/dist/lib/Logger/Logger';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';
import IDokladGetContactConnector from './IDokladGetContactConnector';

export const NAME = 'i-doklad-lookup-contact-from-cloud-payload';

/**
 * Derived connector: extracts companyId (IČO) from the Cloud payload,
 * looks up the contact in iDoklad, and merges the partnerId back.
 *
 * Input:  { invoiceId, companyId, subject, duzp, currency, items }
 * Output: { ...input, partnerId: number }
 */
export default class IDokladLookupContactFromCloudPayload extends IDokladGetContactConnector {

    public getName(): string {
        return NAME;
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const cloudPayload = dto.getJsonData() as Record<string, unknown>;

        logger.info(
            `[DEBUG] LookupContact input: ${JSON.stringify(cloudPayload)}`,
            dto,
            true,
        );

        const companyId = cloudPayload.companyId as string;

        if (!companyId) {
            dto.setStopProcess(ResultCode.STOP_AND_FAILED, 'Missing companyId in Cloud payload.');
            return dto;
        }

        // Reshape dto for the base connector (expects { identificationNumber })
        dto.setJsonData({ identificationNumber: companyId });
        await super.processAction(dto);

        // Parse the iDoklad response
        const response = JSON.parse(dto.getData()) as IContactListResponse;
        const items = response?.Data?.Items;

        if (!items || items.length === 0) {
            dto.setStopProcess(
                ResultCode.STOP_AND_FAILED,
                `No contact found in iDoklad for IČO: ${companyId}`,
            );
            return dto;
        }

        const partnerId = items[0].Id;

        // Merge partnerId back into the original Cloud payload
        dto.setJsonData({ ...cloudPayload, partnerId });

        return dto;
    }

}

/* eslint-disable @typescript-eslint/naming-convention */
interface IContactListResponse {
    Data?: {
        Items?: { Id: number }[];
        TotalItems?: number;
    };
}
/* eslint-enable @typescript-eslint/naming-convention */
