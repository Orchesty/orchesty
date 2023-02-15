import { Comparator, IInput, IOutput } from '../../src/service/comparator';

// eslint-disable-next-line
test('skip', () => {
    expect(true).toBeTruthy();
});

// eslint-disable-next-line
export class ComparatorMock extends Comparator {

    public async compare(input: IInput): Promise<IOutput> {
        const result: IOutput = {
            created: [],
            updated: [],
            deleted: [],
        };
        input.items.forEach((item, index) => {
            switch (index % 3) {
                case 0:
                    result.created.push(item);
                    break;
                case 1:
                    result.updated.push(item);
                    break;
                default:
                    result.deleted.push(item.id as string);
                    break;
            }
        });

        return result;
    }

}
