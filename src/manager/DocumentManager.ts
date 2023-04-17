import Joi from 'joi';
import { Collection, Document, Filter, ObjectId } from 'mongodb';
import Mongo from '../database/Mongo';
import DocumentEnum from '../enum/DocumentEnum';

export default class DocumentManager {

    public constructor(private readonly mongo: Mongo) {
    }

    public async saveDocuments(document: DocumentEnum, entity: Document | Document[]): Promise<void> {
        const col = this.mongo.getCollection(document);
        if (entity instanceof Array) {
            await Promise.all(entity.map(async (e) => this.upsertDocument(col, e)));
        } else {
            await this.upsertDocument(col, entity);
        }
    }

    public async getDocuments(document: DocumentEnum, requestQuery: IRequestQueryParams): Promise<Document[]> {
        const query = this.makeQueryObject(requestQuery);
        const filter = this.getDocumentFilter(document, query);
        let cursor = this.mongo.getCollection(document).find(filter);
        if (query.sorter) {
            cursor = cursor.sort({ created: query.sorter.created === 'asc' ? 1 : -1 });
        }
        if (query.paging) {
            cursor.skip(query.paging.offset ?? 0).limit(query.paging.limit ?? 100);
        }

        return cursor.toArray();
    }

    public async deleteDocuments(document: DocumentEnum, requestQuery: IRequestQueryParams): Promise<number> {
        const query = this.makeQueryObject(requestQuery);
        const filter = this.getDocumentFilter(document, query, true);
        return (await this.mongo.getCollection(document).updateMany(
            filter,
            { $set: { deleted: new Date() } },
        )).matchedCount;
    }

    private async upsertDocument(collection: Collection, _entity: Document): Promise<void> {
        const entity = _entity;
        const id = entity._id;
        delete entity._id;
        if (id) {
            await collection.updateOne({ _id: new ObjectId(id) }, { $set: entity });
        } else {
            await collection.insertOne(entity);
        }
    }

    private getDocumentFilter(
        document: DocumentEnum,
        query: IQueryParams,
        emptyFilterThrowError?: boolean,
    ): Filter<Document> {
        const filter: Filter<Document> = {};
        if (query.filter) {
            if (query.filter.ids) {
                filter._id = { $in: query.filter.ids.map((id) => new ObjectId(id)) };
            }
            if (query.filter.deleted !== undefined && query.filter.deleted !== null) {
                if (!query.filter.deleted) {
                    filter.deleted = { $eq: false };
                }
            } else {
                filter.deleted = { $eq: false };
            }

            switch (document) {
                case DocumentEnum.APPLICATION_INSTALL:
                    if (this.isApplicationQuery(query.filter)) {
                        if (typeof query.filter.names === 'object' && (query.filter.names as { nin: string[] }).nin) {
                            this.addFilterField(filter, 'key', 'nin', (query.filter.names as { nin: string[] }).nin);
                        } else {
                            this.addFilterField(filter, 'key', 'in', query.filter.names);
                        }
                        this.addFilterField(filter, 'user', 'in', query.filter.users);
                        if (query.filter.enabled !== null) {
                            this.addFilterField(filter, 'enabled', 'eq', query.filter.enabled ?? true);
                        }
                        this.addFilterField(filter, 'expires', 'lte', query.filter.expires);
                        Object.entries(query.filter.nonEncrypted ?? {}).forEach(([key, value]) => {
                            let filterValue: number[] | string[];
                            if (typeof value === 'object') {
                                filterValue = Object.values(value)[0] as string[];
                            } else {
                                filterValue = [value];
                            }

                            this.addFilterField(filter, `nonEncrypted.${key}`, 'in', filterValue);
                        });
                    }
                    break;
                case DocumentEnum.NODE:
                    if (this.isNodeQuery(query.filter)) { /* empty */ }
                    break;
                case DocumentEnum.WEBHOOK:
                    if (this.isWebhookQuery(query.filter)) {
                        this.addFilterField(filter, 'app', 'in', query.filter.apps);
                        this.addFilterField(filter, 'user', 'in', query.filter.users);
                    }
                    break;
                default:
                    throw Error('Unsupported document.');
            }
        }

        if (Object.keys(filter).length === 0) {
            if (emptyFilterThrowError) {
                throw Error('Empty filter is not supported.');
            }
            return { deleted: { $eq: false } };
        }

        return filter;
    }

    private isNodeQuery(filter: IApplicationFilter | INodeFilter | IWebhookFilter): filter is INodeFilter {
        const inputSchema = Joi.object<INodeFilter>({
            ids: Joi.array().items(Joi.string()),
        });

        const result = inputSchema.validate(filter);
        if (result.error) {
            throw new Error(result.error.message);
        }

        return true;
    }

    private isWebhookQuery(filter: IApplicationFilter | INodeFilter | IWebhookFilter): filter is IWebhookFilter {
        const inputSchema = Joi.object<IWebhookFilter>({
            ids: Joi.array().items(Joi.string()),
            apps: Joi.array().items(Joi.string()),
            users: Joi.array().items(Joi.string()),
            deleted: Joi.boolean().allow(null),
        });

        const result = inputSchema.validate(filter);
        if (result.error) {
            throw new Error(result.error.message);
        }

        return true;
    }

    private isApplicationQuery(
        filter: IApplicationFilter | INodeFilter | IWebhookFilter,
    ): filter is IApplicationFilter {
        const inputSchema = Joi.object<IApplicationFilter>({
            ids: Joi.array().items(Joi.string()),
            names: Joi.alternatives().try(
                Joi.array().items(Joi.string()),
                Joi.object({ nin: Joi.array().items(Joi.string()) }),
            ),
            users: Joi.array().items(Joi.string()),
            expires: Joi.string(),
            nonEncrypted: Joi.object<Record<string, number>>({}).pattern(Joi.string(), Joi.any()),
            enabled: Joi.boolean().allow(null),
            deleted: Joi.boolean().allow(null),
        });

        const result = inputSchema.validate(filter);
        if (result.error) {
            throw new Error(result.error.message);
        }

        return true;
    }

    private addFilterField(filter: Filter<Document>, field: string, search: 'eq' | 'in' | 'lte' | 'nin', value?: unknown): void {
        if (value) {
            let searchValue;
            switch (search) {
                case 'eq':
                    searchValue = { $eq: value };
                    break;
                case 'in':
                    searchValue = { $in: value };
                    break;
                case 'nin':
                    searchValue = { $nin: value };
                    break;
                case 'lte':
                    searchValue = { $lte: value };
                    break;
                default:
                    throw Error('unsupported operator');
            }
            // eslint-disable-next-line no-param-reassign
            filter[field] = searchValue;
        }
    }

    private makeQueryObject(query: IRequestQueryParams): IQueryParams {
        return {
            filter: query.filter ? JSON.parse(query.filter) : undefined,
            sorter: query.sorter ? JSON.parse(query.sorter) : undefined,
            paging: query.paging ? JSON.parse(query.paging) : undefined,
        };
    }

}

interface IRequestQueryParams {
    filter?: string;
    sorter?: string;
    paging?: string;
}

interface IQueryParams {
    filter?: IApplicationFilter | INodeFilter | IWebhookFilter;
    sorter?: {
        created: 'asc' | 'desc';
    };
    paging?: {
        limit?: number;
        offset?: number;
    };
}

interface IApplicationFilter extends IBaseFilter {
    enabled: boolean | null;
    names?: string[] | { nin: string[] };
    users?: string[];
    expires?: number;
    nonEncrypted?: Record<string, Record<string, unknown>>;
}

type INodeFilter = IBaseFilter;

interface IWebhookFilter extends IBaseFilter {
    apps?: string[];
    users?: string[];
}

interface IBaseFilter {
    ids?: string[];
    deleted?: boolean;
}
