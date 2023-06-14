import { CollectionEnum } from '../../base/enums/CollectionEnum';
import Mongo from '../../base/storage/mongo/Mongo';
import { IClientSearchQuery } from '../../controllers/clients';
import Client, { IContact } from '../entities/Client';
import BaseService from './BaseService';

export default class ClientService extends BaseService<Client, IClientSearchQuery> {

    public constructor(db: Mongo) {
        super(db.getCloudCollection(CollectionEnum.CLIENT));
    }

    protected mapRecordToExport(client: Client): Client {
        return {
            ...super.mapRecordToExport(client),
            tenantId: client.tenantId,
            companyName: client.companyName,
            invoicingId: client.invoicingId,
            contact: this.mapContact(client.contact ?? []),
            supportHourlyRate: client.supportHourlyRate,
            supportSubscription: client.supportSubscription,
            supportResponseTime: client.supportResponseTime,
            hourlyRate: client.hourlyRate ?? null,
            note: client.note ?? null,
        };
    }

    private mapContact(contact: IContact[]): IContact[] {
        return contact.map((item) => ({
            name: item.name ?? null,
            email: item.email ?? null,
            phone: item.phone ?? null,
        }));
    }

}
