import { Collection, ObjectId } from 'mongodb';
import { IClientCreateQuery, IClientSearchQuery } from '../controllers/clients';
import Client, { IContact } from '../entities/Client';
import { CollectionEnum } from '../enums/CollectionEnum';
import ClientCreationError from '../errors/ClientCreationError';
import ClientDeleteError from '../errors/ClientDeleteError';
import ClientSearchError from '../errors/ClientSearchError';
import BillingMongo from '../storage/mongo/Mongo';

export default class ClientService {

    private readonly cloudCollection: Collection;

    public constructor(private readonly db: BillingMongo) {
        this.cloudCollection = this.db.getCloudCollection(CollectionEnum.CLIENT);
    }

    public async getClientsList(query: IClientSearchQuery): Promise<{ rows: Client[] }> {
        let clients;

        try {
            clients = await this.cloudCollection.find(query).toArray() as Client[];
        } catch (e) {
            throw new ClientSearchError((e as Error).message);
        }

        return { rows: this.mapClientRecordsToExport(clients) };
    }

    public async getClient(query: IClientSearchQuery): Promise<{ client: Client }> {
        let client;

        try {
            client = await this.cloudCollection.findOne({
                _id: new ObjectId(query.clientId),
            }) as Client;
        } catch (e) {
            throw new ClientSearchError((e as Error).message);
        }

        return { client: this.mapClientRecordToExport(client) };
    }

    public async createClient(query: IClientCreateQuery): Promise<{ client: Client }> {
        let client;

        try {
            const result = await this.cloudCollection.insertOne(query);
            client = await this.cloudCollection.findOne({ _id: result.insertedId }) as Client;
        } catch (e) {
            throw new ClientCreationError((e as Error).message);
        }

        return { client: this.mapClientRecordToExport(client) };
    }

    public async updateClient(query: IClientCreateQuery): Promise<{ client: Client }> {
        let client;

        try {
            const clientId = new ObjectId(query.clientId);
            // eslint-disable-next-line no-param-reassign
            delete query.clientId;
            await this.cloudCollection.updateOne({ _id: clientId }, { $set: query });
            client = await this.cloudCollection.findOne({ _id: clientId }) as Client;
        } catch (e) {
            throw new ClientCreationError((e as Error).message);
        }

        return { client: this.mapClientRecordToExport(client) };
    }

    public async deleteClient(query: IClientSearchQuery): Promise<{ msg: string }> {
        try {
            await this.cloudCollection.deleteOne({ _id: new ObjectId(query.clientId) });
        } catch (e) {
            throw new ClientDeleteError((e as Error).message);
        }

        return { msg: 'Client successfully deleted!' };
    }

    private mapClientRecordsToExport(clients: Client[]): Client[] {
        return clients.map((client) => this.mapClientRecordToExport(client));
    }

    private mapClientRecordToExport(client: Client): Client {
        return {
            id: client.id,
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
