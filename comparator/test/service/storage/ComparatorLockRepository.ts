import { container } from '@orchesty/nodejs-sdk';
import { ComparatorLockRepository } from '../../../src/service/storage/repository';

describe('ComparatorLockRepository', () => {
    it('Locking', async () => {
        const repository = container.get(ComparatorLockRepository);
        const key = 'test_lock_master_key';

        let aquired = await repository.acquireLock(key);
        expect(aquired).toBeTruthy();

        aquired = await repository.acquireLock(key);
        expect(aquired).toBeFalsy();

        const locks = await repository.findMany({ masterKey: key });
        const { ttl } = locks[0];

        const now = new Date().getTime();
        expect(ttl).toBeGreaterThan(now);
        expect(ttl).toBeLessThan(now + 5 * 60_000);
    });
});
