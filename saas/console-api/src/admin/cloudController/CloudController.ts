import { app } from '../../base/config/config';
import Cloud from './impl/Cloud';
import ICloudBridge from './impl/ICloudBridge';
import Null from './impl/Null';

export default class CloudController {

    private readonly driver: ICloudBridge;

    public constructor() {
        if (app.cloudBridge) {
            this.driver = new Cloud();
        } else {
            this.driver = new Null();
        }
    }

    public async create(instanceDisplayName: string): Promise<string> {
        return this.driver.create(instanceDisplayName);
    }

    public async remove(instanceDisplayName: string): Promise<boolean> {
        return this.driver.remove(instanceDisplayName);
    }

}
