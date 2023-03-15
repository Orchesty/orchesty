import { container } from '@orchesty/nodejs-sdk';
import { ObjectId } from 'mongodb';
import { ComparatorHashRepository } from '../../../src/service/storage/repository';

describe('Repository', () => {
    it('CRUD', async () => {
        const key = 'master_key';
        const repository = container.get(ComparatorHashRepository);
        let hash = {
            id: '',
            masterKey: key,
            hash: '1',
            externalId: 'id',
            ttl: undefined,
        };

        const stored = await repository.insert(hash);
        let fetched = await repository.findById(stored.id);
        expect(stored.id).toEqual(fetched?.id);
        expect('id').toEqual(fetched?.externalId);

        fetched = await repository.findById(stored.id);
        expect(stored.id).toEqual(fetched?.id);
        expect('id').toEqual(fetched?.externalId);

        hash = {
            id: new ObjectId().toHexString(),
            masterKey: key,
            hash: '1',
            externalId: 'id2',
            ttl: undefined,
        };
        const upserted = await repository.upsert(hash);
        fetched = await repository.findById(upserted.id);
        expect(hash.id).toEqual(fetched?.id);
        expect('id2').toEqual(fetched?.externalId);

        hash.externalId = 'id3';
        await repository.upsert(hash);
        fetched = await repository.findById(upserted.id);
        expect('id3').toEqual(fetched?.externalId);
    });
});
