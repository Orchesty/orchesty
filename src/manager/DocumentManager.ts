import Joi from 'joi';
import { Document, Filter } from 'mongodb';
import Mongo from '../database/Mongo';
import DocumentEnum from '../enum/DocumentEnum';

export default class DocumentManager {

    public constructor(private readonly mongo: Mongo) {
    }

    public async saveDocuments(document: DocumentEnum, entity: Document | Document[]): Promise<void> {
        if (entity instanceof Array) {
            await this.mongo.getCollection(document).insertMany(entity);
        } else {
            await this.mongo.getCollection(document).insertOne(entity);
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
        const filter = this.getDocumentFilter(document, query);
        if (Object.keys(filter).length !== 0) {
            return (await this.mongo.getCollection(document).deleteMany(filter)).deletedCount;
        }
        throw Error('Empty filter is not supported.');
    }

    private getDocumentFilter(document: DocumentEnum, query: IQueryParams): Filter<Document> {
        if (query.filter) {
            const filter: Filter<Document> = {};
            if (query.filter.ids) {
                filter.ids = { $in: query.filter.ids };
            }
            switch (document) {
                case DocumentEnum.APPLICATION_INSTALL:
                    if (this.isApplicationQuery(query.filter)) {
                        this.addFilterField(filter, 'key', 'in', query.filter.names);
                        this.addFilterField(filter, 'user', 'in', query.filter.users);
                        if (query.filter.enabled !== null) {
                            this.addFilterField(filter, 'enabled', 'eq', query.filter.enabled ?? true);
                        }
                        this.addFilterField(filter, 'expires', 'lte', query.filter.expires);
                        this.addFilterField(filter, 'nonEncrypted', 'in', query.filter.nonEncrypt);
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
            return filter;
        }

        return {};
    }

    private isNodeQuery(filter: IApplicationFilter | INodeFilter | IWebhookFilter): filter is INodeFilter {
        const inputSchema = Joi.object<INodeFilter>({
            ids: Joi.array().items(Joi.string()),
        });

        const result = inputSchema.validate(filter);
        return result.error === undefined;
    }

    private isWebhookQuery(filter: IApplicationFilter | INodeFilter | IWebhookFilter): filter is IWebhookFilter {
        const inputSchema = Joi.object<IWebhookFilter>({
            ids: Joi.array().items(Joi.string()),
            apps: Joi.array().items(Joi.string()),
            users: Joi.array().items(Joi.string()),
        });

        const result = inputSchema.validate(filter);
        return result.error === undefined;
    }

    private isApplicationQuery(
        filter: IApplicationFilter | INodeFilter | IWebhookFilter,
    ): filter is IApplicationFilter {
        const inputSchema = Joi.object<IApplicationFilter>({
            ids: Joi.array().items(Joi.string()),
            names: Joi.array().items(Joi.string()),
            users: Joi.array().items(Joi.string()),
            expires: Joi.number(),
            nonEncrypt: Joi.object<Record<string, number>>({}).pattern(Joi.string(), Joi.any()),
            enabled: Joi.boolean().allow(null),

        });

        const result = inputSchema.validate(filter);
        return result.error === undefined;
    }

    private addFilterField(filter: Filter<Document>, field: string, search: 'eq' | 'in' | 'lte', value?: unknown): void {
        if (value) {
            let searchValue;
            switch (search) {
                case 'eq':
                    searchValue = { $eq: value };
                    break;
                case 'in':
                    searchValue = { $in: value };
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
    sorter?: string;paging?:
    string;
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
    names?: string[];
    users?: string[];
    expires?: number;
    nonEncrypt?: Record<string, unknown>;
    enabled: boolean | null;
}

type INodeFilter = IBaseFilter;

interface IWebhookFilter extends IBaseFilter {
    apps?: string[];
    users?: string[];
}

interface IBaseFilter {
    ids?: string[];
}
