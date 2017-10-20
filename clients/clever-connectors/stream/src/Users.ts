import * as bodyParser from "body-parser";
import * as express from "express";
import logger from "lib-nodejs/dist/src/logger/Logger";
import {ISubscribeData} from "./StreamServer";

export interface IStreamHttpServerSettings {
    port: number;
    routes: {
        login: string;
        logout: string;
    };
}

class Users {

    private users: { [userId: string]: string[] };

    /**
     *
     * @param {IStreamHttpServerSettings} serverSettings
     */
    constructor(private serverSettings: IStreamHttpServerSettings) {
        this.users = {};
        this.startServer();
    }

    /**
     * Returns boolean whether user can subscribe to group or not
     *
     * @param {string} userId
     * @param {string} group
     * @return {boolean}
     */
    public canAccessGroup(userId: string, group: string): boolean {
        if (userId in this.users) {
            if (this.users[userId].indexOf(group) > -1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Starts the http server for collection information about logged users
     */
    public startServer() {
        const app = express();
        app.use(bodyParser.json());

        app.post(
            this.serverSettings.routes.login,
            (req: express.Request, res: express.Response) => {
                try {
                    const data = this.validateHttpRequest(req);
                    this.addUser(data.userId, data.groups);
                    res.send(this.serverSettings.routes.login);
                } catch (err) {
                    res.status(400).send(err.message);
                }
            },
        );

        app.post(
            this.serverSettings.routes.logout,
            (req: express.Request, res: express.Response) => {
                try {
                    const data = this.validateHttpRequest(req);
                    this.removeUser(data.userId);
                    res.send(this.serverSettings.routes.logout);
                } catch (err) {
                    res.status(400).send(err.message);
                }
            },
        );

        app.listen(this.serverSettings.port);

        logger.info(`Http server listening on port: ${this.serverSettings.port}`);
    }

    /**
     * Validates http request
     *
     * @param {e.Request} req
     * @return {ISubscribeData}
     */
    private validateHttpRequest(req: express.Request): ISubscribeData {
        if (!req.body) {
            throw new Error("Missing request body. Make sure yoy send JSON and content-type=application/json header");
        }

        if (!req.body.userId) {
            throw new Error("Invalid 'userId'.");
        }

        if (!req.body.groups || !Array.isArray(req.body.groups)) {
            throw new Error("Invalid 'groups'.");
        }

        req.body.groups.forEach((groupId: any) => {
            const type = typeof groupId;
            if (type !== "string") {
                throw new Error(`Invalid 'groupId'. It must be string, but '${type}' provided.`);
            }
        });

        return {
            userId: req.body.userId,
            groups: req.body.groups,
        };
    }

    /**
     *
     * @param {string} userId
     * @param {string[]} groups
     */
    private addUser(userId: string, groups: string[]): void {
        logger.info(`Granting '${userId}' access to ${JSON.stringify(groups)}`);

        this.users[userId] = groups;
    }

    /**
     *
     * @param {string} userId
     */
    private removeUser(userId: string): void {
        logger.info(`Revoking '${userId}' access to ${JSON.stringify(this.users[userId])}`);

        delete this.users[userId];
    }

}

export default Users;
