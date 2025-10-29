import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import ResultCode from '@orchesty/nodejs-sdk/dist/lib/Utils/ResultCode';

export default class SleepAndStop extends ACommonNode {

    public getName(): string {
        return 'sleep-and-stop';
    }

    public async processAction(dto: ProcessDto): Promise<ProcessDto> {
        const sleep = this.getRandomInt(1, 10) * 1_000;
        const resultCode = await new Promise<ResultCode>((resolve) => {
            setTimeout(() => {
                resolve(this.getRandomInt(1, 10) % 2 === 0 ? ResultCode.DO_NOT_CONTINUE : ResultCode.STOP_AND_FAILED);
            }, sleep);
        });

        return dto.setStopProcess(resultCode, `Slept for ${sleep / 1000} seconds!`);
    }

    private getRandomInt(minimum: number, maximum: number): number {
        return Math.floor(Math.random() * (maximum - minimum + 1)) + minimum;
    }

}
