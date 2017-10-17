import * as express from "express";
import * as bodyParser from "body-parser";
import logger from "lib-nodejs/dist/src/logger/Logger";

export interface IStreamHttpServerSettings {
    port: number;
    routes: {
        login: string;
        logout: string;
    };
}

class Users {

    private users: { [userId: string]: string[] };

    constructor(private serverSettings: IStreamHttpServerSettings) {
        this.users = {};
        this.startServer();
    }

    public canAccessGroup(userId: string, group: string): boolean {
        if (userId in this.users) {
            if (this.users[userId].indexOf(group) > -1) {
                return true;
            }
        }

        return false;
    }

    public addUser(userId: string, groups: string[]): void {
        this.users[userId] = groups;
    }

    public removeUser(userId: string): void {
        delete this.users[userId];
    }

    public startServer() {
        const app = express();
        app.use(bodyParser.json());

        app.post(
            this.serverSettings.routes.login,
            (req: express.Request, res: express.Response) => {
                console.log(req.body);
                res.send("ok");
            },
        );

        app.post(
            this.serverSettings.routes.logout,
            (req: express.Request, res: express.Response) => {
                res.send("ok");
            },
        );

        app.listen(this.serverSettings.port);

        logger.info(`Http server listening on port: ${this.serverSettings.port}`);
    }

}

export default Users;