import { container } from '@orchesty/nodejs-sdk';
import { Comparator } from '../../../src/service/comparator';
import { ComparatorHashRepository } from '../../../src/service/storage/repository';
import input from './Data/Comparator/input.json';
import input1 from './Data/Comparator/input-1.json';
import output from './Data/Comparator/output.json';
import output1 from './Data/Comparator/output-1.json';

describe('Comparator', () => {
    it('compare multiple objects', async () => {
        const repository = container.get(ComparatorHashRepository);

        const comparator = new Comparator(repository);

        const result = await comparator.compare({
            configuration: { idField: 'customId', masterKey: 'myMasterKey' },
            items: input,
        });
        expect(result).toEqual(output);

        const result1 = await comparator.compare({
            configuration: { idField: 'customId', masterKey: 'myMasterKey' },
            items: input1,
        });
        expect(result1).toEqual(output1);
    });

    it.skip('performance test -> skip time consuming', async () => {
        const repository = container.get(ComparatorHashRepository);
        const numberOfItems = 100_000;

        const comparator = new Comparator(repository);

        const bigInput = [];

        for (let i = 0; i < numberOfItems; i++) {
            bigInput.push({
                customId: i,
                image: {
                    src: 'Images/Sun.png',
                    name: 'sun1',
                    hOffset: 250,
                    vOffset: 250,
                    alignment: 'center',
                },
            });
        }

        const from = new Date().getTime();
        await comparator.compare({
            configuration: { idField: 'customId', masterKey: 'myMasterKey' },
            items: bigInput,
        });

        const to = new Date().getTime();
        const slicedBigInput = bigInput.slice(numberOfItems / 2);

        const from2 = new Date().getTime();
        await comparator.compare({
            configuration: { idField: 'customId', masterKey: 'myMasterKey' },
            items: slicedBigInput,
        });
        const to2 = new Date().getTime();
        // eslint-disable-next-line
        console.log('Number of items:', numberOfItems, ', time spent:', to - from, 'ms', 'second run:', numberOfItems / 2, ':', to2 - from2, 'ms');

        expect(true).toBeTruthy();
    });
});
