import { Application } from 'express';
import DocumentEnum, { isDocumentSupported } from '../enum/DocumentEnum';
import DocumentManager from '../manager/DocumentManager';

export default class DocumentRouter {

    public constructor(private readonly app: Application, private readonly documentManager: DocumentManager) {
    }

    public initRoutes(): void {
    // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.get('/document/:document', async (req, res) => {
            const { document } = req.params;

            if (!isDocumentSupported(document)) {
                res.statusCode = 400;
                res.json({ message: { error: `Unsupported document [${document}]` } });
                return;
            }

            try {
                const result = await this.documentManager.getDocuments(document as DocumentEnum, req.query);
                res.json(result);
            } catch (e) {
                if (e instanceof Error) {
                    res.json({ message: { error: e.message } });
                }
                res.json({ message: 'Worker-api: mongo unknown error' });
            }
        });

        // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.post('/document/:document', async (req, res) => {
            const { document } = req.params;

            if (!isDocumentSupported(document)) {
                res.statusCode = 400;
                res.json({ message: { error: `Unsupported document [${document}]` } });
                return;
            }

            try {
                await this.documentManager.saveDocuments(document as DocumentEnum, req.body);
                res.json({ message: { status: 'OK', data: '' } });
            } catch (e) {
                if (e instanceof Error) {
                    res.json({ message: { error: e.message } });
                }
                res.json({ message: 'Worker-api: mongo unknown error' });
            }
        });

        // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.delete('/document/:document', async (req, res) => {
            const { document } = req.params;

            if (!isDocumentSupported(document)) {
                res.statusCode = 400;
                res.json({ message: { error: `Unsupported document [${document}]` } });
                return;
            }

            try {
                const deleted = await this.documentManager.deleteDocuments(document as DocumentEnum, req.query);
                res.json({ message: { status: 'OK', data: { deleted } } });
            } catch (e) {
                if (e instanceof Error) {
                    res.json({ message: { error: e.message } });
                }
                res.json({ message: 'Worker-api: mongo unknown error' });
            }
        });
    }

}
