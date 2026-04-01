import { config } from 'dotenv';
config();

import { container, initiateContainer, listen } from '@orchesty/nodejs-sdk';
import { OAuth2Provider } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Provider/OAuth2/OAuth2Provider';
import SmtpSendEmail from '@orchesty/connector-smtp/dist/Connector/SmtpSendEmail';
import SlackSendMessageConnector from '@orchesty/connector-slack/dist/Connectors/SlackSendMessageConnector';
import SmtpApplicationWithInfo from './Application/SmtpApplicationWithInfo';
import SlackApplicationWithInfo from './Application/SlackApplicationWithInfo';
import InviteEmailMapper from './CustomNode/InviteEmailMapper';
import RestoreAccessEmailMapper from './CustomNode/RestoreAccessEmailMapper';
import CloudInviteEmailMapper from './CustomNode/CloudInviteEmailMapper';
import CloudRestoreAccessEmailMapper from './CustomNode/CloudRestoreAccessEmailMapper';

function prepare(): void {
    initiateContainer();

    const oauth2Provider = container.get(OAuth2Provider);

    // ── Applications ──
    const smtpApp = new SmtpApplicationWithInfo();
    container.setApplication(smtpApp);

    const slackApp = new SlackApplicationWithInfo(oauth2Provider);
    container.setApplication(slackApp);

    // ── Connectors & Batches ──
    container.setNode(new SmtpSendEmail(), smtpApp);
    container.setNode(new SlackSendMessageConnector(), slackApp);

    // ── Custom Nodes (enterprise instance) ──
    container.setNode(new InviteEmailMapper());
    container.setNode(new RestoreAccessEmailMapper());

    // ── Custom Nodes (cloud) ──
    container.setNode(new CloudInviteEmailMapper());
    container.setNode(new CloudRestoreAccessEmailMapper());
}

prepare();
listen();
