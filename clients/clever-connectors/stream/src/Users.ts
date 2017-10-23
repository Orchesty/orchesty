import * as bodyParser from "body-parser";
import * as express from "express";
import logger from "lib-nodejs/dist/src/logger/Logger";
import * as uuidV4 from "uuid/v4";
import {ISubscribeData} from "./StreamServer";

export interface IStreamHttpServerSettings {
    port: number;
    routes: {
        login: string;
        logout: string;
    };
}

/**
 * TODO - use redis for storing user data instead of in-memory storage
 */
class Users {

    private users: { [token: string]: { userId: string, groups: string[]} };

    /**
     *
     * @param {IStreamHttpServerSettings} serverSettings
     */
    constructor(private serverSettings: IStreamHttpServerSettings) {
        this.users = {};
        this.startServer();
    }

    /**
     * Returns boolean whether user has valid token and if he can subscribe to given group
     *
     * @param {string} token
     * @param {string} userId
     * @param {string} group
     * @return {boolean}
     */
    public canAccessGroup(token: string, userId: string, group: string): boolean {
        if (!(token in this.users)) {
            return false;
        }

        const user = this.users[token];
        if (user.userId !== userId) {
            return false;
        }

        return user.groups.indexOf(group) >= 0;
    }

    /**
     * Starts the http server for collection information about logged users
     */
    public startServer() {
        const app = express();
        app.use(bodyParser.json());

        /**
         * LogIn route
         */
        app.post(
            this.serverSettings.routes.login,
            (req: express.Request, res: express.Response) => {
                res.set("content-type", "application/json");

                try {
                    const data = this.validateLoginRequest(req);
                    const token = this.addUser(data.userId, data.groups);

                    res.send(JSON.stringify({ userId: data.userId, token }));
                } catch (err) {
                    res.status(400).send(JSON.stringify({error: err.message}));
                }
            },
        );

        /**
         * LogOut route
         */
        app.post(
            this.serverSettings.routes.logout,
            (req: express.Request, res: express.Response) => {
                res.set("content-type", "application/json");

                try {
                    const token = this.validateLogoutRequest(req);
                    const userId = this.removeUser(token);

                    res.send(JSON.stringify({ userId }));
                } catch (err) {
                    res.status(400).send(JSON.stringify({error: err.message}));
                }
            },
        );

        app.listen(this.serverSettings.port);

        logger.info(`Http server listening on port: ${this.serverSettings.port}`);
    }

    /**
     * Validates login http request
     *
     * @param {e.Request} req
     * @return {ISubscribeData}
     */
    private validateLoginRequest(req: express.Request): {userId: string, groups: string[]} {
        if (!req.body) {
            throw new Error("Missing request body. Make sure yoy sent JSON and content-type=application/json header");
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
     * Validates logout http request
     *
     * @param {e.Request} req
     * @return {string}
     */
    private validateLogoutRequest(req: express.Request): string {
        if (!req.body) {
            throw new Error("Missing request body. Make sure you sent JSON and content-type=application/json header");
        }

        if (!req.body.token) {
            throw new Error("Invalid 'token'.");
        }

        return req.body.token;
    }

    /**
     *
     * @param {string} userId
     * @param {string[]} groups
     * @return {string}
     */
    private addUser(userId: string, groups: string[]): string {
        const token = uuidV4();
        this.users[token] = {userId, groups};

        logger.info(`Granting '${userId}' access to ${JSON.stringify(groups)}. Token: ${token}`);

        return token;
    }

    /**
     *
     * @param {string} token
     */
    private removeUser(token: string): string {
        if (!this.users[token]) {
            logger.warn(`Trying to remove user with non-existing token '${token}'`);
            return;
        }

        const user = this.users[token];
        delete this.users[token];

        logger.info(`Revoking '${user.userId}' access to ${JSON.stringify(user.groups)}`);

        return user.userId;
    }

}

export default Users;
