import axios from 'axios';
import { app } from '../../../base/config/config';
import CreationError from '../../errors/CreationError';
import ICloudBridge from './ICloudBridge';

export default class Cloud implements ICloudBridge {

    public async create(instanceDisplayName: string): Promise<string> {
        const { cloudControllerUri } = app;

        const resp = await axios.post<{ instance: string }>(
            `${cloudControllerUri}/instance`,
            {
                body: { instanceDisplayName },
                headers: {
                    'Content-Type': 'application/json',
                },
            },
        );

        if (resp.status >= 300) {
            throw new CreationError('Could not create cloud!');
        }

        return resp.data.instance;
    }

    public async remove(instanceDisplayName: string): Promise<boolean> {
        const { cloudControllerUri } = app;

        const resp = await axios.delete(
            `${cloudControllerUri}/instance`,
            {
                data: { instanceDisplayName },
                headers: {
                    'Content-Type': 'application/json',
                },
            },
        );

        if (resp.status >= 300) {
            throw new CreationError('Could not delete cloud!');
        }

        return true;
    }

}
