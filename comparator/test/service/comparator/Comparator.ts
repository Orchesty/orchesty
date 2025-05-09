import { container } from '@orchesty/nodejs-sdk';
import { Comparator } from '../../../src/service/comparator';
import RedisStorage from '../../../src/storage/RedisStorage';
import input from './Data/Comparator/input.json';
import input1 from './Data/Comparator/input-1.json';
import output from './Data/Comparator/output.json';
import output1 from './Data/Comparator/output-1.json';

let redisStorage: RedisStorage;
let comparator: Comparator;

describe('Comparator', () => {
    beforeAll(() => {
        redisStorage = container.get(RedisStorage);
        comparator = new Comparator(redisStorage);
    });

    it('compare multiple objects', async () => {
        const result = await comparator.compare(
            {
                configuration: { idField: 'customId', masterKey: 'myMasterKey' },
                items: input,
            },
            '1',
        );

        expect(result).toEqual(output);

        const result1 = await comparator.compare(
            {
                configuration: { idField: 'customId', masterKey: 'myMasterKey' },
                items: input1,
            },
            '1',
        );

        expect(result1).toEqual(output1);
        // Deleted is not part of output1 as it's added later by CustomNode
    });

    it('performance test', async () => {
        const numberOfItems = 50_000;
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
        await comparator.compare(
            {
                configuration: { idField: 'customId', masterKey: 'myMasterKey' },
                items: bigInput,
            },
            '1',
        );

        const to = new Date().getTime();
        const slicedBigInput = bigInput.slice(numberOfItems / 2);

        const from2 = new Date().getTime();
        await comparator.compare(
            {
                configuration: { idField: 'customId', masterKey: 'myMasterKey' },
                items: slicedBigInput,
            },
            '1',
        );
        const to2 = new Date().getTime();
        // eslint-disable-next-line
        console.log('Number of items:', numberOfItems, ', time spent:', to - from, 'ms', 'second run:', numberOfItems / 2, ':', to2 - from2, 'ms');

        expect(true).toBeTruthy();
    });
});
