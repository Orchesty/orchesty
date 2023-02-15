import { container } from '@orchesty/nodejs-sdk';
import { ComparatorHash } from '../../../src/model';
import { ComparatorHashRepository } from '../../../src/service/storage/repository';

describe('Repository', () => {
    it('CRUD', async () => {
        const key = 'master_key';
        const repository = container.get(ComparatorHashRepository);
        let hash = new ComparatorHash(key, '1', 'id', undefined);

        const stored = await repository.insert(hash);
        let fetched = await repository.get(stored._id);
        expect(stored._id).toEqual(fetched?._id);
        expect('id').toEqual(fetched?.externalId);

        fetched = await repository.get(stored._id.toHexString());
        expect(stored._id).toEqual(fetched?._id);
        expect('id').toEqual(fetched?.externalId);

        hash = new ComparatorHash(key, '1', 'id2', undefined);
        const upserted = await repository.upsert(hash);
        fetched = await repository.get(upserted._id.toHexString());
        expect(hash._id).toEqual(fetched?._id);
        expect('id2').toEqual(fetched?.externalId);

        hash.externalId = 'id3';
        await repository.upsert(hash);
        fetched = await repository.get(upserted._id.toHexString());
        expect('id3').toEqual(fetched?.externalId);
    });
});
