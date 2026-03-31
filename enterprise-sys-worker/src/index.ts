import { config } from 'dotenv';
config();

import { container, initiateContainer, listen } from '@orchesty/nodejs-sdk';
import { OAuth2Provider } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Provider/OAuth2/OAuth2Provider';
import SmtpApplication from '@orchesty/connector-smtp/dist/SmtpApplication';
import SmtpSendEmail from '@orchesty/connector-smtp/dist/Connector/SmtpSendEmail';
import SlackApplication from '@orchesty/connector-slack/dist/SlackApplication';
import SlackSendMessageConnector from '@orchesty/connector-slack/dist/Connectors/SlackSendMessageConnector';
import InviteEmailMapper from './CustomNode/InviteEmailMapper';
import RestoreAccessEmailMapper from './CustomNode/RestoreAccessEmailMapper';

function prepare(): void {
    initiateContainer();

    const oauth2Provider = container.get(OAuth2Provider);

    // ── Applications ──
    const smtpApp = new SmtpApplication();
    container.setApplication(smtpApp);

    const slackApp = new SlackApplication(oauth2Provider);
    container.setApplication(slackApp);

    // ── Connectors & Batches ──
    container.setNode(new SmtpSendEmail(), smtpApp);
    container.setNode(new SlackSendMessageConnector(), slackApp);

    // ── Custom Nodes ──
    container.setNode(new InviteEmailMapper());
    container.setNode(new RestoreAccessEmailMapper());
}

prepare();
listen();
