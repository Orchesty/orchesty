import ICloudBridge from './ICloudBridge';

export default class Null implements ICloudBridge {

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public async create(instanceDisplayName: string): Promise<string> {
        return Promise.resolve('instanceId');
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public async remove(instanceDisplayName: string): Promise<boolean> {
        return Promise.resolve(true);
    }

}
