import { Collection, ObjectId } from 'mongodb';
import { ISearchQuery } from '../controllers/baseController';
import BaseEntity from '../entities/BaseEntity';
import CreationError from '../errors/CreationError';
import DeleteError from '../errors/DeleteError';
import SearchError from '../errors/SearchError';

export default class BaseService<IEntity extends BaseEntity, IQuery extends ISearchQuery> {

    public constructor(protected readonly collection: Collection) {
    }

    public async list(query: IQuery): Promise<{ rows: IEntity[] }> {
        let entities;

        const q = {
            ...query,
            deleted: {
                $exists: false,
            },
        };

        try {
            entities = await this.collection.find(q).toArray() as IEntity[];
        } catch (e) {
            throw new SearchError((e as Error).message);
        }

        return { rows: this.mapRecordsToExport(entities) };
    }

    public async get(query: IQuery): Promise<IEntity> {
        let entity;

        try {
            entity = await this.collection.findOne({
                _id: new ObjectId(query._id),
                deleted: {
                    $exists: false,
                },
            }) as IEntity;
        } catch (e) {
            throw new SearchError((e as Error).message);
        }

        if (!entity) {
            throw new SearchError('Entity not found!');
        }

        return this.mapRecordToExport(entity);
    }

    public async create(query: IQuery): Promise<IEntity> {
        let entity;

        try {
            const result = await this.collection.insertOne(query);
            entity = await this.collection.findOne({ _id: result.insertedId }) as IEntity;
        } catch (e) {
            throw new CreationError((e as Error).message);
        }

        return this.mapRecordToExport(entity);
    }

    public async update(query: IQuery): Promise<IEntity> {
        let entity;

        try {
            const objectId = new ObjectId(query._id);
            // eslint-disable-next-line no-param-reassign
            delete query._id;
            await this.collection.updateOne({
                _id: objectId,
                deleted: {
                    $exists: false,
                },
            }, { $set: query });
            entity = await this.collection.findOne({
                _id: objectId,
                deleted: {
                    $exists: false,
                },
            }) as IEntity;
        } catch (e) {
            throw new CreationError((e as Error).message);
        }

        if (!entity) {
            throw new SearchError('Entity not found!');
        }

        return this.mapRecordToExport(entity);
    }

    public async delete(query: IQuery): Promise<{ msg: string }> {
        try {
            await this.collection.updateOne({
                _id: new ObjectId(query._id),
                deleted: {
                    $exists: false,
                },
            }, {
                $set: {
                    deleted: new Date(),
                },
            });
        } catch (e) {
            throw new DeleteError((e as Error).message);
        }

        return { msg: 'Entity successfully deleted!' };
    }

    protected mapRecordToExport(entity: IEntity): IEntity {
        return {
            _id: entity._id,
            deleted: entity.deleted ?? null,
        } as IEntity;
    }

    protected mapRecordsToExport(entities: IEntity[]): IEntity[] {
        return entities.map((entity) => this.mapRecordToExport(entity));
    }

}
