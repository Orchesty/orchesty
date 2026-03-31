import SlackApplication from '@orchesty/connector-slack/dist/SlackApplication';
import { OAuth2Provider } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Provider/OAuth2/OAuth2Provider';
import path from 'path';

export default class SlackApplicationWithInfo extends SlackApplication {
    constructor(provider: OAuth2Provider) {
        super(provider);
        this.infoFilename = path.join(__dirname, 'slack-readme.md');
    }
}
