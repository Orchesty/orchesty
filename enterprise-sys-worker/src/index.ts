import SlackSendMessageConnector from '@orchesty/connector-slack/dist/Connectors/SlackSendMessageConnector';
import SmtpSendEmail from '@orchesty/connector-smtp/dist/Connector/SmtpSendEmail';
import { container, initiateContainer } from '@orchesty/nodejs-sdk';
import { OAuth2Provider } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Provider/OAuth2/OAuth2Provider';
import { config } from 'dotenv';
import EnterpriseOpenAIApplication from './Application/EnterpriseOpenAIApplication';
import SlackApplicationWithInfo from './Application/SlackApplicationWithInfo';
import SmtpApplicationWithInfo from './Application/SmtpApplicationWithInfo';
import ZaiApplication from './Application/ZaiApplication';
import OpenAITrace from './Connector/OpenAITrace';
import ZaiTrace from './Connector/ZaiTrace';
import AdminForgotPasswordEmailMapper from './CustomNode/AdminForgotPasswordEmailMapper';
import AdminInviteEmailMapper from './CustomNode/AdminInviteEmailMapper';
import AdminRestoreAccessEmailMapper from './CustomNode/AdminRestoreAccessEmailMapper';
import CloudForgotPasswordEmailMapper from './CustomNode/CloudForgotPasswordEmailMapper';
import CloudInviteEmailMapper from './CustomNode/CloudInviteEmailMapper';
import CloudLimitThresholdEmailMapper from './CustomNode/CloudLimitThresholdEmailMapper';
import CloudNotificationMapper from './CustomNode/CloudNotificationMapper';
import CloudRestoreAccessEmailMapper from './CustomNode/CloudRestoreAccessEmailMapper';
import ForgotPasswordEmailMapper from './CustomNode/ForgotPasswordEmailMapper';
import InviteEmailMapper from './CustomNode/InviteEmailMapper';
import LimitOverflowEmailMapper from './CustomNode/LimitOverflowEmailMapper';
import LimitRecoveredEmailMapper from './CustomNode/LimitRecoveredEmailMapper';
import RestoreAccessEmailMapper from './CustomNode/RestoreAccessEmailMapper';
import TopologyFailedEmailMapper from './CustomNode/TopologyFailedEmailMapper';
import TopologyFailedMessageEmailMapper from './CustomNode/TopologyFailedMessageEmailMapper';
import TopologyFailedRepeatedlyEmailMapper from './CustomNode/TopologyFailedRepeatedlyEmailMapper';
import TopologySlowEmailMapper from './CustomNode/TopologySlowEmailMapper';

config();

export function prepare(): void {
    initiateContainer();

    const oauth2Provider = container.get(OAuth2Provider);

    // ── Applications ──
    const smtpApp = new SmtpApplicationWithInfo();
    container.setApplication(smtpApp);

    const slackApp = new SlackApplicationWithInfo(oauth2Provider);
    container.setApplication(slackApp);

    const openAITrace = new OpenAITrace();
    const openAIApp = new EnterpriseOpenAIApplication(openAITrace);
    openAITrace.setApplication(openAIApp);
    container.setApplication(openAIApp);

    const zaiTrace = new ZaiTrace();
    const zaiApp = new ZaiApplication(zaiTrace);
    zaiTrace.setApplication(zaiApp);
    container.setApplication(zaiApp);

    // ── Connectors & Batches ──
    container.setNode(new SmtpSendEmail(), smtpApp);
    container.setNode(new SlackSendMessageConnector(), slackApp);
    container.setNode(openAITrace, openAIApp);
    container.setNode(zaiTrace, zaiApp);

    // ── Custom Nodes (enterprise instance) ──
    container.setNode(new InviteEmailMapper());
    container.setNode(new RestoreAccessEmailMapper());

    // ── Custom Nodes (cloud) ──
    container.setNode(new CloudInviteEmailMapper());
    container.setNode(new CloudRestoreAccessEmailMapper());

    // ── Custom Nodes (admin) ──
    container.setNode(new AdminInviteEmailMapper());
    container.setNode(new AdminRestoreAccessEmailMapper());
    container.setNode(new AdminForgotPasswordEmailMapper());

    // ── Custom Nodes (forgot password) ──
    container.setNode(new ForgotPasswordEmailMapper());
    container.setNode(new CloudForgotPasswordEmailMapper());

    // ── Custom Nodes (notifications) ──
    container.setNode(new TopologyFailedEmailMapper());
    container.setNode(new TopologyFailedRepeatedlyEmailMapper());
    container.setNode(new TopologyFailedMessageEmailMapper());
    container.setNode(new TopologySlowEmailMapper());
    container.setNode(new LimitOverflowEmailMapper());
    container.setNode(new LimitRecoveredEmailMapper());
    container.setNode(new CloudLimitThresholdEmailMapper());

    // ── Custom Nodes (cloud notifications) ──
    container.setNode(new CloudNotificationMapper());
}
