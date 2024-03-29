import { Application, Request, Response } from 'express';
import DocumentEnum, { isDocumentSupported } from '../enum/DocumentEnum';
import { logger } from '../logger/Logger';
import DocumentManager from '../manager/DocumentManager';

export default class DocumentRouter {

    public constructor(private readonly app: Application, private readonly documentManager: DocumentManager) {
    }

    public initRoutes(): void {
    // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.get('/document/:document', async (req, res) => {
            const { document } = req.params;

            if (!isDocumentSupported(document)) {
                this.sendResponse(req, res, { message: { error: `Unsupported document [${document}]` } }, 400);
                return;
            }

            try {
                const result = await this.documentManager.getDocuments(document as DocumentEnum, req.query);
                this.sendResponse(req, res, result, 200);
            } catch (e) {
                if (e instanceof Error) {
                    this.sendResponse(req, res, { message: { error: e.message } }, 400);
                    return;
                }
                this.sendResponse(req, res, { message: 'Worker-api: mongo unknown error' }, 400);
            }
        });

        // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.post('/document/:document', async (req, res) => {
            const { document } = req.params;

            if (!isDocumentSupported(document)) {
                this.sendResponse(req, res, { message: { error: `Unsupported document [${document}]` } }, 400);
                return;
            }

            try {
                await this.documentManager.saveDocuments(document as DocumentEnum, req.body);
                this.sendResponse(req, res, { message: { status: 'OK', data: '' } }, 200);
            } catch (e) {
                if (e instanceof Error) {
                    this.sendResponse(req, res, { message: { error: e.message } }, 400);
                    return;
                }
                this.sendResponse(req, res, { message: 'Worker-api: mongo unknown error' }, 400);
            }
        });

        // eslint-disable-next-line @typescript-eslint/no-misused-promises
        this.app.delete('/document/:document', async (req, res) => {
            const { document } = req.params;

            if (!isDocumentSupported(document)) {
                this.sendResponse(req, res, { message: { error: `Unsupported document [${document}]` } }, 400);
                return;
            }

            try {
                const deleted = await this.documentManager.deleteDocuments(document as DocumentEnum, req.query);
                this.sendResponse(req, res, { message: { status: 'OK', data: { deleted } } }, 200);
            } catch (e) {
                if (e instanceof Error) {
                    this.sendResponse(req, res, { message: { error: e.message } }, 400);
                    return;
                }
                this.sendResponse(req, res, { message: 'Worker-api: mongo unknown error' }, 400);
            }
        });
    }

    private sendResponse(req: Request, res: Response, body: unknown, status: number): void {
        if (status !== 200) {
            logger.error({
                url: '/document',
                params: req.params,
                query: req.query,
                response: { status, body },
            });
        } else {
            logger.debug({
                url: '/document',
                params: req.params,
                query: req.query,
                response: { status, body },
            });
        }

        if (status) {
            res.statusCode = status;
        }
        res.json(body);
    }

}
