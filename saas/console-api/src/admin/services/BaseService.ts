import { Collection, ObjectId } from 'mongodb';
import { ISearchQuery } from '../../controllers/baseController';
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
            ...this.replaceDateFields(query),
            deleted: {
                $eq: null,
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
                    $eq: null,
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
        const q = this.replaceDateFields(query);
        q.created = new Date();
        let entity;

        try {
            const result = await this.collection.insertOne(q);
            entity = await this.collection.findOne({ _id: result.insertedId }) as IEntity;
        } catch (e) {
            throw new CreationError((e as Error).message);
        }

        return this.mapRecordToExport(entity);
    }

    public async update(query: IQuery): Promise<IEntity> {
        const q = this.replaceDateFields(query);
        let entity;

        try {
            const objectId = new ObjectId(q._id);
            // eslint-disable-next-line no-param-reassign
            delete q._id;
            q.updated = new Date();
            await this.collection.updateOne({
                _id: objectId,
                deleted: {
                    $eq: null,
                },
            }, { $set: q });
            entity = await this.collection.findOne({
                _id: objectId,
                deleted: {
                    $eq: null,
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
        const q = query._id ? { _id: new ObjectId(query._id) } : { ...query };
        try {
            await this.collection.updateOne({
                ...q,
                deleted: {
                    $eq: null,
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
            created: entity.created ?? null,
            updated: entity.updated ?? null,
            deleted: entity.deleted ?? null,
        } as IEntity;
    }

    protected mapRecordsToExport(entities: IEntity[]): IEntity[] {
        return entities.map((entity) => this.mapRecordToExport(entity));
    }

    protected getEntityDateFields(): string[] {
        return ['created', 'updated', 'deleted'];
    }

    private replaceDateFields(query: IQuery): IQuery {
        return Object.fromEntries(Object.entries(query).map(([key, item]) => {
            if (item && this.getEntityDateFields().includes(key)) {
                return [key, new Date(item)];
            }
            return [key, item];
        })) as IQuery;
    }

}
