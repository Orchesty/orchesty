import FlexiBeeApplication from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import PipedriveApplication from '@orchesty/connector-pipedrive/dist/PipedriveApplication';
import SlackSendMessageConnector from '@orchesty/connector-slack/dist/Connectors/SlackSendMessageConnector';
import SmtpSendEmail from '@orchesty/connector-smtp/dist/Connector/SmtpSendEmail';
import { container, initiateContainer } from '@orchesty/nodejs-sdk';
import { OAuth2Provider } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Provider/OAuth2/OAuth2Provider';
import DatabaseClient from '@orchesty/nodejs-sdk/dist/lib/Storage/Database/Client';
import CurlSender from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/CurlSender';
import { config } from 'dotenv';
import EnterpriseOpenAIApplication from './Application/EnterpriseOpenAIApplication';
import SlackApplicationWithInfo from './Application/SlackApplicationWithInfo';
import SmtpApplicationWithInfo from './Application/SmtpApplicationWithInfo';
import ZaiApplication from './Application/ZaiApplication';
import CloudCallbackApplication from './Cloud/CloudCallbackApplication';
import CloudCallbackConnector from './Cloud/Connector/CloudCallbackConnector';
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
import TrialEndedEmailMapper from './CustomNode/TrialEndedEmailMapper';
import TrialReminderEmailMapper from './CustomNode/TrialReminderEmailMapper';
import EcomailSendMessageConnector from './Ecomail/Connector/EcomailSendMessageConnector';
import EcomailSendSalesBusinessNotificationConnector from './Ecomail/Connector/EcomailSendSalesBusinessNotificationConnector';
import EcomailSendSalesCustomerConfirmationConnector from './Ecomail/Connector/EcomailSendSalesCustomerConfirmationConnector';
import EcomailSendTransactionalEmailConnector from './Ecomail/Connector/EcomailSendTransactionalEmailConnector';
import EcomailSubscribeNewsletterConnector from './Ecomail/Connector/EcomailSubscribeNewsletterConnector';
import EcomailApplication from './Ecomail/EcomailApplication';
import FlexiBeeCreateFakturaVydanaFromInvoice from './FlexiBee/Connector/FlexiBeeCreateFakturaVydanaFromInvoice';
import FlexiBeeCreatePartnerFromInvoice from './FlexiBee/Connector/FlexiBeeCreatePartnerFromInvoice';
import FlexiBeeLookupPartnerFromInvoice from './FlexiBee/Connector/FlexiBeeLookupPartnerFromInvoice';
import FlexiBeeUploadAttachmentConnector from './FlexiBee/Connector/FlexiBeeUploadAttachmentConnector';
import IDokladListIssuedInvoicesBatch from './IDoklad/Batch/IDokladListIssuedInvoicesBatch';
import IDokladCreateInvoiceFromCloud from './IDoklad/Connector/IDokladCreateInvoiceFromCloud';
import IDokladDownloadInvoicePdfConnector from './IDoklad/Connector/IDokladDownloadInvoicePdfConnector';
import IDokladGetIssuedInvoiceByVsConnector from './IDoklad/Connector/IDokladGetIssuedInvoiceByVsConnector';
import IDokladLookupContactFromCloudPayload from './IDoklad/Connector/IDokladLookupContactFromCloudPayload';
import IDokladPrepareInvoiceFromCloud from './IDoklad/Connector/IDokladPrepareInvoiceFromCloud';
import IDokladTagIssuedInvoiceConnector from './IDoklad/Connector/IDokladTagIssuedInvoiceConnector';
import IDokladUploadAttachmentToInvoiceConnector from './IDoklad/Connector/IDokladUploadAttachmentToInvoiceConnector';
import FilterSyncedInvoices from './IDoklad/CustomNode/FilterSyncedInvoices';
import MockIssuedInvoiceData from './IDoklad/CustomNode/MockIssuedInvoiceData';
import PrepareInvoiceFilters from './IDoklad/CustomNode/PrepareInvoiceFilters';
import IDokladClientCredentialsApplication from './IDoklad/IDokladClientCredentialsApplication';
import CloudToIDokladInvoiceMapper from './Mapper/CloudToIDokladInvoiceMapper';
import InvoiceIDokladToFlexiMapper from './Mapper/InvoiceIDokladToFlexiMapper';
import PipedriveAddSalesLeadConnector from './Pipedrive/Connector/PipedriveAddSalesLeadConnector';
import PipedriveAddSalesNoteConnector from './Pipedrive/Connector/PipedriveAddSalesNoteConnector';
import PipedriveAddSalesOrganizationConnector from './Pipedrive/Connector/PipedriveAddSalesOrganizationConnector';
import PipedriveAddSalesPersonConnector from './Pipedrive/Connector/PipedriveAddSalesPersonConnector';

config();

export function prepare(): void {
    initiateContainer();

    const oauth2Provider = container.get(OAuth2Provider);

    // ── Applications (existing enterprise) ──
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

    // ── Applications (migrated from stage-worker) ──
    const ecomailApp = new EcomailApplication();
    container.setApplication(ecomailApp);

    const idokladApp = new IDokladClientCredentialsApplication();
    container.setApplication(idokladApp);

    const cloudCallbackApp = new CloudCallbackApplication();
    container.setApplication(cloudCallbackApp);

    const pipedriveApp = new PipedriveApplication();
    container.setApplication(pipedriveApp);

    const flexiBeeApp = new FlexiBeeApplication(
        container.get(CurlSender),
        container.get(DatabaseClient),
    );
    container.setApplication(flexiBeeApp);

    // ── Connectors (existing enterprise) ──
    container.setNode(new SmtpSendEmail(), smtpApp);
    container.setNode(new SlackSendMessageConnector(), slackApp);
    container.setNode(openAITrace, openAIApp);
    container.setNode(zaiTrace, zaiApp);

    // ── Connectors (Ecomail) ──
    container.setNode(new EcomailSendTransactionalEmailConnector(), ecomailApp);
    container.setNode(new EcomailSendMessageConnector(), ecomailApp);
    container.setNode(new EcomailSubscribeNewsletterConnector(), ecomailApp);
    container.setNode(new EcomailSendSalesBusinessNotificationConnector(), ecomailApp);
    container.setNode(new EcomailSendSalesCustomerConfirmationConnector(), ecomailApp);

    // ── Connectors (Pipedrive) ──
    container.setNode(new PipedriveAddSalesOrganizationConnector(), pipedriveApp);
    container.setNode(new PipedriveAddSalesPersonConnector(), pipedriveApp);
    container.setNode(new PipedriveAddSalesLeadConnector(), pipedriveApp);
    container.setNode(new PipedriveAddSalesNoteConnector(), pipedriveApp);

    // ── Connectors (FlexiBee) ──
    container.setNode(new FlexiBeeLookupPartnerFromInvoice(), flexiBeeApp);
    container.setNode(new FlexiBeeCreatePartnerFromInvoice(), flexiBeeApp);
    container.setNode(new FlexiBeeCreateFakturaVydanaFromInvoice(), flexiBeeApp);
    container.setNode(new FlexiBeeUploadAttachmentConnector(), flexiBeeApp);

    // ── Connectors & Batches (iDoklad) ──
    container.setNode(new IDokladListIssuedInvoicesBatch(), idokladApp);
    container.setNode(new IDokladGetIssuedInvoiceByVsConnector(), idokladApp);
    container.setNode(new IDokladDownloadInvoicePdfConnector(), idokladApp);
    container.setNode(new IDokladTagIssuedInvoiceConnector(), idokladApp);
    container.setNode(new IDokladLookupContactFromCloudPayload(), idokladApp);
    container.setNode(new IDokladPrepareInvoiceFromCloud(), idokladApp);
    container.setNode(new IDokladCreateInvoiceFromCloud(), idokladApp);
    container.setNode(new IDokladUploadAttachmentToInvoiceConnector(), idokladApp);

    // ── Connectors (Cloud callback) ──
    container.setNode(new CloudCallbackConnector(), cloudCallbackApp);

    // ── Custom Nodes — system email mappers (Ecomail-bound) ──
    // Bound to ecomailApp so they can read system sender settings (from_email, from_name)
    // directly from the install via getSystemSender(dto).
    container.setNode(new InviteEmailMapper(), ecomailApp);
    container.setNode(new RestoreAccessEmailMapper(), ecomailApp);
    container.setNode(new ForgotPasswordEmailMapper(), ecomailApp);

    container.setNode(new CloudInviteEmailMapper(), ecomailApp);
    container.setNode(new CloudRestoreAccessEmailMapper(), ecomailApp);
    container.setNode(new CloudForgotPasswordEmailMapper(), ecomailApp);
    container.setNode(new CloudLimitThresholdEmailMapper(), ecomailApp);

    container.setNode(new AdminInviteEmailMapper(), ecomailApp);
    container.setNode(new AdminRestoreAccessEmailMapper(), ecomailApp);
    container.setNode(new AdminForgotPasswordEmailMapper(), ecomailApp);

    container.setNode(new TopologyFailedEmailMapper(), ecomailApp);
    container.setNode(new TopologyFailedRepeatedlyEmailMapper(), ecomailApp);
    container.setNode(new TopologyFailedMessageEmailMapper(), ecomailApp);
    container.setNode(new TopologySlowEmailMapper(), ecomailApp);

    container.setNode(new LimitOverflowEmailMapper(), ecomailApp);
    container.setNode(new LimitRecoveredEmailMapper(), ecomailApp);

    // ── Custom Nodes — trial email mappers (Ecomail-bound) ──
    container.setNode(new TrialReminderEmailMapper(), ecomailApp);
    container.setNode(new TrialEndedEmailMapper(), ecomailApp);

    // ── Custom Nodes — pure-data mappers / filters (no application context) ──
    container.setNode(new CloudNotificationMapper());
    container.setNode(new PrepareInvoiceFilters());
    container.setNode(new FilterSyncedInvoices());
    container.setNode(new MockIssuedInvoiceData());
    container.setNode(new InvoiceIDokladToFlexiMapper());
    container.setNode(new CloudToIDokladInvoiceMapper());
}
