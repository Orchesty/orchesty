import { container } from '@orchesty/nodejs-sdk';
import { SDK } from '@orchesty/nodejs-sdk/dist/lib/Utils/Headers';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import TopologyTester from '@orchesty/nodejs-sdk/dist/test/Testers/TopologyTester';
import path from 'path';
import { DEFAULT_SDK, DEFAULT_USER } from '../../../test/DataProvider';
import { prepare } from '../../../test/TestAbstract';

let tester: TopologyTester;
const TOPOLOGY_PATH = path.resolve(
    process.cwd(),
    'src',
    'topology',
    'json-placeholder-beeceptor',
    'json-placeholder-to-beeceptor-posts.tplg',
);

describe('Tests for JsonPlaceholderToBeeceptorPosts topology', () => {
    beforeAll(async () => {
        tester = new TopologyTester(container, __filename, true, ['Activity_1he0su7']);
        await prepare();
    });

    it('run JsonPlaceholderToBeeceptorPosts manually', async () => {
        const dto = new ProcessDto();
        dto.setUser(DEFAULT_USER);
        dto.addHeader(SDK, DEFAULT_SDK);
        dto.setJsonData({});

        await tester.runTopology(TOPOLOGY_PATH, dto);
    });
});
