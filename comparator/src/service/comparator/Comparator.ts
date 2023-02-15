import crypto from 'crypto';
import { ComparatorHash } from '../../model';
import { ComparatorHashRepository } from '../storage/repository';
import { IConfiguration, IInput, IOutput } from './types';

interface IHash {
    data: Record<string, unknown>;
    hash: string;
}

export class Comparator {

    private readonly hashAlgorithm = 'sha1';

    public constructor(private readonly comparatorHashRepository: ComparatorHashRepository) {
    }

    public async compare(input: IInput): Promise<IOutput> {
        const hashes: Record<string, IHash> = {};
        const { idField, excludedFields, masterKey } = input.configuration;

        input.items.forEach((item) => {
            hashes[item[idField] as string] = { data: item, hash: this.createHash(item, excludedFields) };
        });
        const dbHashes = await this.comparatorHashRepository.find({ masterKey });

        return this.compareDbHashes(dbHashes, hashes, input.configuration);
    }

    public async compareDbHashes(
        dbHashes: ComparatorHash[],
        hashes: Record<string, IHash>,
        settings: IConfiguration,
    ): Promise<IOutput> {
        const result: IOutput = {
            created: [],
            updated: [],
            deleted: [],
        };

        const promises: Promise<unknown>[] = [];

        dbHashes.forEach((item) => {
            const dataHash = hashes?.[item.externalId];
            if (dataHash) {
                if (item.hash !== dataHash.hash) {
                    result.updated.push(dataHash.data);
                    promises.push(this.updateHash(item, dataHash.hash));
                }

                delete hashes[item.externalId];
            } else if (settings.deleted ?? true) {
                result.deleted.push(item.externalId);
            }
        });

        result.created = Object.values(hashes).map((item) => item.data);
        promises.push(this.insertHashes(hashes, settings));

        await this.comparatorHashRepository.delete(
            {
                externalId: {
                    $in: result.deleted,
                },
            },
        );

        await Promise.all(promises);

        return result;
    }

    public createHash(data: object, excludedFields: string[] = []): string {
        const hasher = crypto.createHash(this.hashAlgorithm);
        this.clearData(data, excludedFields);

        hasher.update(JSON.stringify(data));
        return hasher.digest('hex').toString();
    }

    private async insertHashes(hashes: Record<string, IHash>, settings: IConfiguration): Promise<unknown> {
        let items: ComparatorHash[] = [];
        const promises: Promise<void>[] = [];

        Object.values(hashes).forEach(
            (item) => {
                items.push(new ComparatorHash(
                    settings.masterKey,
                    item.hash,
                    item.data[settings.idField] as string,
                    this.getTtl(settings.ttl),
                ));

                if (items.length >= 100) {
                    promises.push(this.comparatorHashRepository.insertMany(items));
                    items = [];
                }
            },
        );

        if (items.length > 0) {
            promises.push(this.comparatorHashRepository.insertMany(items));
        }

        return Promise.all(promises);
    }

    private async updateHash(dbHash: ComparatorHash, hash: string): Promise<void> {
        await this.comparatorHashRepository.updateHash(dbHash.externalId, hash, dbHash.ttl);
    }

    private getTtl(ttl?: number): Date | undefined {
        if (!ttl) {
            return undefined;
        }

        const date = new Date();
        date.setSeconds(date.getSeconds() + ttl);

        return date;
    }

    private clearData(data: any, excludedFields: string[]): void {
        excludedFields.forEach((path) => {
            const keys = path.split('.');
            const last = keys.length - 1;

            const local = keys.slice(0, last).reduce((acc, key) => acc?.[key] ?? null, data);

            if (local) {
                delete local[keys[last]];
            }
        });
    }

}
