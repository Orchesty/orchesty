import { container } from '@orchesty/nodejs-sdk';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import TopologyTester from '@orchesty/nodejs-sdk/dist/test/Testers/TopologyTester';
import path from 'path';
import { DEFAULT_USER } from '../../../test/DataProvider';
import { prepare } from '../../../test/TestAbstract';

let tester: TopologyTester;
const TOPOLOGY_PATH = path.resolve(
    process.cwd(),
    'src',
    'topology',
    'mysql-beeceptor',
    'mysql-to-beeceptor-products.tplg',
);

describe('Tests for MySqlToBeeceptorProducts topology', () => {
    beforeAll(async () => {
        tester = new TopologyTester(container, __filename, true, [
            'Activity_0v5945u', 'Activity_0qq3qff', 'Activity_09pfau6', 'Activity_0rrt44t',
        ]);
        await prepare();
    });

    it('run MySqlToBeeceptorProducts manually', async () => {
        const dto = new ProcessDto();
        dto.setUser(DEFAULT_USER);
        dto.setJsonData({});

        await tester.runTopology(TOPOLOGY_PATH, dto, undefined, 'product');
    });
});
