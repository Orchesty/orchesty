import { IClientSearchQuery } from '../controllers/clients';
import Client, { IContact } from '../entities/Client';
import { CollectionEnum } from '../enums/CollectionEnum';
import Mongo from '../storage/mongo/Mongo';
import BaseService from './BaseService';

export default class ClientService extends BaseService<Client, IClientSearchQuery> {

    public constructor(db: Mongo) {
        super(db.getCloudCollection(CollectionEnum.CLIENT));
    }

    protected mapRecordToExport(client: Client): Client {
        return {
            _id: client._id,
            iDokladId: client.iDokladId ?? null,
            contact: this.mapContact(client.contact ?? []),
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
