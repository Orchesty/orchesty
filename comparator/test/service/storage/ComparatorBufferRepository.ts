import { container } from '@orchesty/nodejs-sdk';
import { ComparatorBuffer } from '../../../src/model';
import { ComparatorBufferRepository } from '../../../src/service/storage/repository';

describe('ComparatorBufferRepository', () => {
    it('upsertBuffer', async () => {
        const repository = container.get(ComparatorBufferRepository);
        const key = 'g6s5h4sg4d5sfd4g5';

        const buffer = new ComparatorBuffer(key, [{ a: 1 }, { b: 2 }], false);

        let info = await repository.upsertBuffer(buffer);
        expect(info.closed).toBeFalsy();
        expect(info.total).toEqual(2);

        // Ignore same page
        await repository.upsertBuffer(buffer);
        expect(info.closed).toBeFalsy();
        expect(info.total).toEqual(2);

        buffer.data = [{ c: 3 }];
        info = await repository.upsertBuffer(buffer);
        expect(info.closed).toBeFalsy();
        expect(info.total).toEqual(3);

        buffer.closed = true;
        buffer.data = [{ d: 4 }];
        info = await repository.upsertBuffer(buffer);
        expect(info.closed).toBeTruthy();
        expect(info.total).toEqual(4);

        buffer.data = [{ e: 5 }];
        info = await repository.upsertBuffer(buffer);
        expect(info.closed).toBeTruthy();
        expect(info.total).toEqual(5);
    });
});
