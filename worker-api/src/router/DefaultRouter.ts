import { Application, Response } from 'express';
import Mongo from '../database/Mongo';

export default class DefaultRouter {

    public constructor(private readonly app: Application, private readonly mongo: Mongo) {
    }

    public initRoutes(): void {
        // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.get('/', async (req, res) => {
            await this.makeStatusMessage(res);
        });

        // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.get('/status', async (req, res) => {
            await this.makeStatusMessage(res);
        });
    }

    private async makeStatusMessage(res: Response): Promise<void> {
        res.json({ mongo: { connected: await this.mongo.isConnected() } });
    }

}
