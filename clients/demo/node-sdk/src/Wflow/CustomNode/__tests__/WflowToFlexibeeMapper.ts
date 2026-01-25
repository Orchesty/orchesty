import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import crypto from 'crypto';
import { prepare } from '../../../../test/TestAbstract';
import { NAME as WFLOW_TO_FLEXIBEE_MAPPER } from '../WflowToFlexibeeMapper';

let tester: NodeTester;

describe('Tests for WflowToFlexibeeMapper', () => {
    beforeEach(() => {
        tester = new NodeTester(container, __filename);

        // eslint-disable-next-line jest/prefer-mock-return-shorthand, @typescript-eslint/strict-void-return
        jest.spyOn(crypto, 'randomBytes').mockImplementationOnce(() => Buffer.from(
            new Uint8Array([65, 65, 65, 65, 65, 65, 65, 65]).buffer,
        ));

        prepare();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(WFLOW_TO_FLEXIBEE_MAPPER);
    });
});
