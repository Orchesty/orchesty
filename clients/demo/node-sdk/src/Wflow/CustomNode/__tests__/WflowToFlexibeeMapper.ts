import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as WFLOW_TO_FLEXIBEE_MAPPER } from '../WflowToFlexibeeMapper';

let tester: NodeTester;

describe('Tests for WflowToFlexibeeMapper', () => {
    beforeEach(async () => {
        tester = new NodeTester(container, __filename);
        await prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(WFLOW_TO_FLEXIBEE_MAPPER);
    });

    it('process - no lines', async () => {
        await tester.testCustomNode(WFLOW_TO_FLEXIBEE_MAPPER, 'no-lines');
    });

    it('process - no ic', async () => {
        await tester.testCustomNode(WFLOW_TO_FLEXIBEE_MAPPER, 'no-ic');
    });

    it('process - no dic', async () => {
        await tester.testCustomNode(WFLOW_TO_FLEXIBEE_MAPPER, 'no-dic');
    });

    it('process - no due date', async () => {
        await tester.testCustomNode(WFLOW_TO_FLEXIBEE_MAPPER, 'no-due-date');
    });

    it('process - no accounting', async () => {
        await tester.testCustomNode(WFLOW_TO_FLEXIBEE_MAPPER, 'no-accounting');
    });

    it('process - no accounting proforma', async () => {
        await tester.testCustomNode(WFLOW_TO_FLEXIBEE_MAPPER, 'no-accounting-proforma');
    });

    it('process - no vats', async () => {
        await tester.testCustomNode(WFLOW_TO_FLEXIBEE_MAPPER, 'no-vats');
    });
});
