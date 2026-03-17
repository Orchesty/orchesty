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
    'wflow-flexi-bee',
    'flexi-bee-to-wflow-registers.tplg',
);

describe('Tests for FlexiBeeToWflowRegisters topology', () => {
    beforeAll(async () => {
        tester = new TopologyTester(container, __filename, true, [
            'Activity_0oy0w9d',
            'Activity_1d4ynlu',
            'Activity_12h8545',
            'Activity_134wnhx',
            'Activity_0tfi7jh',
            'Activity_1radgxo',
            'Activity_1fkubxa',
        ]);
        await prepare();
    });

    it('run FlexiBeeToWflowRegisters manually', async () => {
        await tester.runTopology(
            TOPOLOGY_PATH,
            new ProcessDto()
                .setUser(DEFAULT_USER)
                .setJsonData({}),
            undefined,
            'cron',
        );
    });
});
