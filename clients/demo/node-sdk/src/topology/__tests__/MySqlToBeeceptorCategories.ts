import { container } from '@orchesty/nodejs-sdk';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import TopologyTester from '@orchesty/nodejs-sdk/dist/test/Testers/TopologyTester';
import path from 'path';
import { DEFAULT_USER } from '../../../test/DataProvider';
import { prepare } from '../../../test/TestAbstract';
import MySqlRepository from '../../Sql/Repository/MySqlRepository';

let tester: TopologyTester;
const TOPOLOGY_PATH = path.resolve(
    process.cwd(),
    'src',
    'topology',
    'mysql-beeceptor',
    'mysql-to-beeceptor-categories.tplg',
);

describe('Tests for MySqlToBeeceptorCategories topology', () => {
    beforeAll(async () => {
        tester = new TopologyTester(container, __filename, true, ['Activity_0qllpx1', 'Activity_1do7apq', 'Activity_1idc9in', 'Activity_0wrp4ez']);
        await prepare();
        await container.get(MySqlRepository).deleteAll();
    });

    it('run MySqlToBeeceptorCategories manually', async () => {
        const dto = new ProcessDto();
        dto.setUser(DEFAULT_USER);
        dto.setJsonData({});

        await tester.runTopology(TOPOLOGY_PATH, dto, undefined, 'start');
    });
});
