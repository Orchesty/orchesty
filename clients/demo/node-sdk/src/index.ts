import AirtableApplication from '@orchesty/nodejs-connectors/dist/lib/Airtable/AirtableApplication';
import LambdaApplication from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/Lambda/LambdaApplication';
import RDSAddRoleToDBCluster from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/RDS/Connector/RDSAddRoleToDBCluster';
import RDSApplication from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/RDS/RDSApplication';
import RedshiftExecuteQueryConnector
    from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/Redshift/Connector/RedshiftExecuteQueryConnector';
import RedshiftApplication from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/Redshift/RedshiftApplication';
import S3Application from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/S3/S3Application';
import SESSendEmail from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/SimpleEmailService/Connector/SESSendEmail';
import AsanaApplication from '@orchesty/nodejs-connectors/dist/lib/Asana/AsanaApplication';
import AsanaCreateTaskConnector from '@orchesty/nodejs-connectors/dist/lib/Asana/Connector/AsanaCreateTaskConnector';
import BigcommerceApplication from '@orchesty/nodejs-connectors/dist/lib/Bigcommerce/BigcommerceApplication';
import BoxApplication from '@orchesty/nodejs-connectors/dist/lib/Box/BoxApplication';
import { EventEnum } from '@orchesty/nodejs-connectors/dist/lib/Common/Events/EventEnum';
import EventStatusFilter from '@orchesty/nodejs-connectors/dist/lib/Common/EventStatusFilter/EventStatusFilter';
import ListUsersCommon from '@orchesty/nodejs-connectors/dist/lib/Common/ListUsers/ListUsers';
import DiscordSendMessageConnector
    from '@orchesty/nodejs-connectors/dist/lib/Discord/Connector/DiscordSendMessageConnector';
import DiscordApplication from '@orchesty/nodejs-connectors/dist/lib/Discord/DiscordApplication';
import DropboxApplication from '@orchesty/nodejs-connectors/dist/lib/Dropbox/DropboxApplication';
import FacebookAdsApplication from '@orchesty/nodejs-connectors/dist/lib/FacebookAds/FacebookAdsApplication';
import FakturoidApplication from '@orchesty/nodejs-connectors/dist/lib/Fakturoid/FakturoidApplication';
import FlexiBeeApplication from '@orchesty/nodejs-connectors/dist/lib/FlexiBee/FexiBeeApplication';
import GoogleCalendarApplication
    from '@orchesty/nodejs-connectors/dist/lib/Google/GoogleCalendar/GoogleCalendarApplication';
import GoogleDriveApplication from '@orchesty/nodejs-connectors/dist/lib/Google/GoogleDrive/GoogleDriveApplication';
import GoogleSheetApplication from '@orchesty/nodejs-connectors/dist/lib/Google/GoogleSheet/GoogleSheetApplication';
import YoutubeApplication from '@orchesty/nodejs-connectors/dist/lib/Google/Youtube/YoutubeApplication';
import HubSpotSendTransactionEmailConnector
    from '@orchesty/nodejs-connectors/dist/lib/Hubspot/Connector/HubSpotSendTransactionEmailConnector';
import HubSpotApplication from '@orchesty/nodejs-connectors/dist/lib/Hubspot/HubSpotApplication';
import HubSpotApplicationBasic from '@orchesty/nodejs-connectors/dist/lib/Hubspot/HubSpotApplicationBasic';
import IDokladApplication from '@orchesty/nodejs-connectors/dist/lib/IDoklad/IDokladApplication';
import JiraCreateIssueConnector from '@orchesty/nodejs-connectors/dist/lib/Jira/Connector/JiraCreateIssueConnector';
import JiraApplication from '@orchesty/nodejs-connectors/dist/lib/Jira/JiraApplication';
import Magento2Application from '@orchesty/nodejs-connectors/dist/lib/Magento2/Magento2Application';
import MailchimpApplication from '@orchesty/nodejs-connectors/dist/lib/Mailchimp/MailchimpApplication';
import MoneyS5Application from '@orchesty/nodejs-connectors/dist/lib/MoneyS5/MoneyS5Application';
import NutshellApplication from '@orchesty/nodejs-connectors/dist/lib/Nutshell/NutshellApplication';
import PipedriveApplication from '@orchesty/nodejs-connectors/dist/lib/Pipedrive/PipedriveApplication';
import QuickBooksApplication from '@orchesty/nodejs-connectors/dist/lib/QuickBooks/QuickBooksApplication';
import SalesForceApplication from '@orchesty/nodejs-connectors/dist/lib/SalesForce/SalesForceApplication';
import SendGridApplication from '@orchesty/nodejs-connectors/dist/lib/SendGrid/SendGridApplication';
import ShipstationApplication from '@orchesty/nodejs-connectors/dist/lib/Shipstation/ShipstationApplication';
import ShopifyApplication from '@orchesty/nodejs-connectors/dist/lib/Shopify/ShopifyApplication';
import ShoptetPremiumApplication from '@orchesty/nodejs-connectors/dist/lib/Shoptet/ShoptetPremiumApplication';
import SlackSendMessageConnector from '@orchesty/nodejs-connectors/dist/lib/Slack/Connectors/SlackSendMessageConnector';
import SlackApplication from '@orchesty/nodejs-connectors/dist/lib/Slack/SlackApplication';
import MariaDbApplication from '@orchesty/nodejs-connectors/dist/lib/Sql/MariaDbApplication';
import MsSqlApplication from '@orchesty/nodejs-connectors/dist/lib/Sql/MsSqlApplication';
import MySqlApplication from '@orchesty/nodejs-connectors/dist/lib/Sql/MySqlApplication';
import OracleDbApplication from '@orchesty/nodejs-connectors/dist/lib/Sql/OracleDbApplication';
import PostgreSqlApplication from '@orchesty/nodejs-connectors/dist/lib/Sql/PostgreSqlApplication';
import SqliteApplication from '@orchesty/nodejs-connectors/dist/lib/Sql/SqliteApplication';
import StripeApplication from '@orchesty/nodejs-connectors/dist/lib/Stripe/StripeApplication';
import TableauApplication from '@orchesty/nodejs-connectors/dist/lib/Tableau/TableauApplication';
import TrelloCreateCardConnector from '@orchesty/nodejs-connectors/dist/lib/Trello/Connector/TrelloCreateCardConnector';
import TrelloApplication from '@orchesty/nodejs-connectors/dist/lib/Trello/TrelloApplication';
import TwilioApplication from '@orchesty/nodejs-connectors/dist/lib/Twilio/TwilioApplication';
import UpgatesApplication from '@orchesty/nodejs-connectors/dist/lib/Upgates/UpgatesApplication';
import WebflowApplication from '@orchesty/nodejs-connectors/dist/lib/Webflow/WebflowApplication';
import WisepopsApplication from '@orchesty/nodejs-connectors/dist/lib/Wisepops/WisepopsApplication';
import WooCommerceApplication from '@orchesty/nodejs-connectors/dist/lib/WooCommerce/WooCommerceApplication';
import XeroApplication from '@orchesty/nodejs-connectors/dist/lib/Xero/XeroApplication';
import ZendeskApplication from '@orchesty/nodejs-connectors/dist/lib/Zendesk/ZendeskApplication';
import ZohoApplication from '@orchesty/nodejs-connectors/dist/lib/Zoho/ZohoApplication';
import ZoomApplication from '@orchesty/nodejs-connectors/dist/lib/Zoom/ZoomApplication';
import { container, initiateContainer } from '@orchesty/nodejs-sdk';
import { OAuth2Provider } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Provider/OAuth2/OAuth2Provider';
import CacheService from '@orchesty/nodejs-sdk/dist/lib/Cache/CacheService';
import CoreServices from '@orchesty/nodejs-sdk/dist/lib/DIContainer/CoreServices';
import MongoDbClient from '@orchesty/nodejs-sdk/dist/lib/Storage/Mongodb/Client';
import Redis from '@orchesty/nodejs-sdk/dist/lib/Storage/Redis/Redis';
import TopologyRunner from '@orchesty/nodejs-sdk/dist/lib/Topology/TopologyRunner';
import CurlSender from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/CurlSender';
import ListPosts from './JsonPlaceholder/Batch/ListPosts';
import ListUsers from './JsonPlaceholder/Batch/ListUsers';
import BinSender from './JsonPlaceholder/Connector/BinSender';
import Node from './JsonPlaceholder/Custom/Node';
import HubSpotCreateContactMapper from './JsonPlaceholder/HubSpotCreateContactMapper';
import NonInstallableApplication from './JsonPlaceholder/NonInstallableApplication';
import SampleApplication from './JsonPlaceholder/SampleApplication';
import TenantApplication from './JsonPlaceholder/TenantApplication';
import HubSpotAddEmailToListConnector from './OrchestyIo/Connector/HubSpotAddEmailToListConnector';
import HubSpotCreateContactConnector from './OrchestyIo/Connector/HubSpotCreateContactConnector';
import HubsoptAddContactToListMapper from './OrchestyIo/CustomNode/HubsoptAddContactToListMapper';
import HubspotToSesTransactionEmailMapper from './OrchestyIo/CustomNode/HubspotToSesEmailMapper';
import OrchestyToHubSpotContactMapper from './OrchestyIo/CustomNode/OrchestyToHubSpotContactMapper';
import OrchestyToJiraMapper from './OrchestyIo/CustomNode/OrchestyToJiraMapper';
import { HubspotListIdsEnums } from './OrchestyIo/Enum/HubspotListIdsEnums';
import { OrchestyPageEnum } from './OrchestyIo/Enum/OrchestyPageEnum';
import SESApplication from './OrchestyIo/SESApplication';

export async function start(): Promise<void> {
    await initiateContainer();
    const sender = container.get<CurlSender>(CoreServices.CURL);
    const mongoDb = container.get<MongoDbClient>(CoreServices.MONGO);
    const provider = container.get<OAuth2Provider>(CoreServices.OAUTH2_PROVIDER);
    const runner = container.get<TopologyRunner>(CoreServices.TOPOLOGY_RUNNER);
    const redis = new Redis('');
    const cache = new CacheService(redis, sender);

    const eventStatusFilterSuccess = new EventStatusFilter(EventEnum.PROCESS_SUCCESS);
    container.setCustomNode(eventStatusFilterSuccess);

    const eventStatusFilterError = new EventStatusFilter(EventEnum.PROCESS_FAILED);
    container.setCustomNode(eventStatusFilterError);

    const eventStatusFilterLimiter = new EventStatusFilter(EventEnum.LIMIT_OVERFLOW);
    container.setCustomNode(eventStatusFilterLimiter);

    const eventStatusFilterTrash = new EventStatusFilter(EventEnum.MESSAGE_IN_TRASH);
    container.setCustomNode(eventStatusFilterTrash);

    const sampleApp = new SampleApplication();
    container.setApplication(sampleApp);

    const tenantApp = new TenantApplication();
    container.setApplication(tenantApp);

    const nonInstallableApp = new NonInstallableApplication();
    container.setApplication(nonInstallableApp);

    const airtableApp = new AirtableApplication();
    container.setApplication(airtableApp);

    const tabletauApp = new TableauApplication(sender, mongoDb);
    container.setApplication(tabletauApp);

    const awsRds = new RDSApplication();
    container.setApplication(awsRds);

    const awsRedshift = new RedshiftApplication();
    container.setApplication(awsRedshift);

    const s3App = new S3Application();
    container.setApplication(s3App);

    const lambdaApp = new LambdaApplication();
    container.setApplication(lambdaApp);

    const sesApp = new SESApplication();
    container.setApplication(sesApp);

    const dropBoxApp = new DropboxApplication(provider);
    container.setApplication(dropBoxApp);

    const facebookApp = new FacebookAdsApplication(provider);
    container.setApplication(facebookApp);

    const stripeApp = new StripeApplication();
    container.setApplication(stripeApp);

    const jiraApp = new JiraApplication();
    container.setApplication(jiraApp);

    const slackApp = new SlackApplication(provider);
    container.setApplication(slackApp);

    const discordApp = new DiscordApplication();
    container.setApplication(discordApp);

    const trelloApp = new TrelloApplication();
    container.setApplication(trelloApp);

    const asanaApp = new AsanaApplication(provider);
    container.setApplication(asanaApp);

    const bigcommerceApplicationApp = new BigcommerceApplication(provider);
    container.setApplication(bigcommerceApplicationApp);

    const fakturoidApp = new FakturoidApplication();
    container.setApplication(fakturoidApp);

    const flexiBeeApp = new FlexiBeeApplication(sender, mongoDb);
    container.setApplication(flexiBeeApp);

    const googleDriveApp = new GoogleDriveApplication(provider);
    container.setApplication(googleDriveApp);

    const googleCallendarApp = new GoogleCalendarApplication(provider);
    container.setApplication(googleCallendarApp);

    const googleSheetApp = new GoogleSheetApplication(provider);
    container.setApplication(googleSheetApp);

    const youtubeApp = new YoutubeApplication(provider);
    container.setApplication(youtubeApp);

    const twilioApp = new TwilioApplication();
    container.setApplication(twilioApp);

    const webflowApp = new WebflowApplication();
    container.setApplication(webflowApp);

    const hubspotApp = new HubSpotApplication(provider);
    container.setApplication(hubspotApp);

    const hubspotAppBasic = new HubSpotApplicationBasic();
    container.setApplication(hubspotAppBasic);

    const idokaldApp = new IDokladApplication(provider);
    container.setApplication(idokaldApp);

    const mailchimpApp = new MailchimpApplication(sender, provider);
    container.setApplication(mailchimpApp);

    const magento2App = new Magento2Application(cache);
    container.setApplication(magento2App);

    const moneyS5App = new MoneyS5Application(cache);
    container.setApplication(moneyS5App);

    const shoptetPremApp = new ShoptetPremiumApplication(runner);
    container.setApplication(shoptetPremApp);

    const shopifyApp = new ShopifyApplication(sender);
    container.setApplication(shopifyApp);

    const nutshellApp = new NutshellApplication();
    container.setApplication(nutshellApp);

    const pipedriveApp = new PipedriveApplication();
    container.setApplication(pipedriveApp);

    const quickbooksApp = new QuickBooksApplication(provider);
    container.setApplication(quickbooksApp);

    const salesForceApp = new SalesForceApplication(provider);
    container.setApplication(salesForceApp);

    const sendGridApp = new SendGridApplication();
    container.setApplication(sendGridApp);

    const shipstationApp = new ShipstationApplication();
    container.setApplication(shipstationApp);

    const wisepopsApp = new WisepopsApplication();
    container.setApplication(wisepopsApp);

    const zendeskApp = new ZendeskApplication(provider);
    container.setApplication(zendeskApp);

    const zohoApp = new ZohoApplication(provider);
    container.setApplication(zohoApp);

    const zoomApp = new ZoomApplication(provider);
    container.setApplication(zoomApp);

    const wooCommerce = new WooCommerceApplication();
    container.setApplication(wooCommerce);

    const upGatesApp = new UpgatesApplication();
    container.setApplication(upGatesApp);

    const mysqlApp = new MySqlApplication();
    container.setApplication(mysqlApp);

    const mssqlApp = new MsSqlApplication();
    container.setApplication(mssqlApp);

    const mariaDbApp = new MariaDbApplication();
    container.setApplication(mariaDbApp);

    const postgresSqlApp = new PostgreSqlApplication();
    container.setApplication(postgresSqlApp);

    const sqlLiteApp = new SqliteApplication();
    container.setApplication(sqlLiteApp);

    const listPosts = new ListPosts();
    listPosts.setSender(sender);
    container.setBatch(listPosts);

    const listUsers = new ListUsers();
    listUsers.setSender(sender);
    container.setBatch(listUsers);

    const binSender = new BinSender();
    binSender.setSender(sender);
    container.setConnector(binSender);

    const hubspotCreateContact = new HubSpotCreateContactConnector();
    hubspotCreateContact
        .setSender(sender)
        .setApplication(hubspotAppBasic)
        .setDb(mongoDb);
    container.setConnector(hubspotCreateContact);

    const hubspotContactMapper = new HubSpotCreateContactMapper();
    hubspotContactMapper
        .setDb(mongoDb)
        .setApplication(hubspotAppBasic);
    container.setCustomNode(hubspotContactMapper);

    const orchestyToHubSpotContactMapper = new OrchestyToHubSpotContactMapper();
    container.setCustomNode(orchestyToHubSpotContactMapper);

    const hubSpotAddEmailToListConnector = new HubSpotAddEmailToListConnector()
        .setSender(sender)
        .setApplication(hubspotAppBasic)
        .setDb(mongoDb);
    container.setConnector(hubSpotAddEmailToListConnector);

    const addContactToHubspotSalesListMapper = new HubsoptAddContactToListMapper(HubspotListIdsEnums.SALES);
    container.setCustomNode(addContactToHubspotSalesListMapper);

    const addContactToHubspotContactListMapper = new HubsoptAddContactToListMapper(HubspotListIdsEnums.CONTACT_FROM);
    container.setCustomNode(addContactToHubspotContactListMapper);

    const addContactToHubspotCommunityListMapper = new HubsoptAddContactToListMapper(HubspotListIdsEnums.COMMUNITY);
    container.setCustomNode(addContactToHubspotCommunityListMapper);

    const addContactToHubspotNewsletterListMapper = new HubsoptAddContactToListMapper(HubspotListIdsEnums.NEWSLETTER);
    container.setCustomNode(addContactToHubspotNewsletterListMapper);

    const hubspotToJiraSalesMapper = new OrchestyToJiraMapper(OrchestyPageEnum.SALES, ['sales']);
    container.setCustomNode(hubspotToJiraSalesMapper);

    const hubspotToJiraContactMapper = new OrchestyToJiraMapper(OrchestyPageEnum.CONTACT, ['contact']);
    container.setCustomNode(hubspotToJiraContactMapper);

    const hubspotToJiraCommunityMapper = new OrchestyToJiraMapper(OrchestyPageEnum.COMMUNITY, ['community']);
    container.setCustomNode(hubspotToJiraCommunityMapper);

    const discordSendMessage = new DiscordSendMessageConnector()
        .setSender(sender)
        .setApplication(discordApp)
        .setDb(mongoDb);
    container.setConnector(discordSendMessage);

    const slackSendMessage = new SlackSendMessageConnector()
        .setSender(sender)
        .setApplication(slackApp);
    container.setConnector(slackSendMessage);

    const asanaCreateTask = new AsanaCreateTaskConnector()
        .setSender(sender)
        .setApplication(asanaApp)
        .setDb(mongoDb);
    container.setConnector(asanaCreateTask);

    const trelloCreateCard = new TrelloCreateCardConnector()
        .setSender(sender)
        .setApplication(trelloApp)
        .setDb(mongoDb);
    container.setConnector(trelloCreateCard);

    const jiraCreateIssue = new JiraCreateIssueConnector()
        .setSender(sender)
        .setApplication(jiraApp)
        .setDb(mongoDb);
    container.setConnector(jiraCreateIssue);

    const awsRdsRoleConnector = new RDSAddRoleToDBCluster()
        .setSender(sender)
        .setApplication(awsRds)
        .setDb(mongoDb);
    container.setConnector(awsRdsRoleConnector);

    const awsSesSendEmail = new SESSendEmail()
        .setSender(sender)
        .setApplication(sesApp)
        .setDb(mongoDb);
    container.setConnector(awsSesSendEmail);

    const redShiftExecQuery = new RedshiftExecuteQueryConnector()
        .setSender(sender)
        .setApplication(awsRedshift)
        .setDb(mongoDb);
    container.setConnector(redShiftExecQuery);

    const node = new Node();
    container.setCustomNode(node);

    const xeroApplication = new XeroApplication(provider);
    container.setApplication(xeroApplication);

    const oracleDbApplication = new OracleDbApplication();
    container.setApplication(oracleDbApplication);

    const boxApplication = new BoxApplication(provider);
    container.setApplication(boxApplication);

    const listUsersCommon = new ListUsersCommon()
        .setApplication(sampleApp)
        .setDb(mongoDb);
    container.setBatch(listUsersCommon);

    const hubspotToHubspotSalesTransactionEmail = new HubspotToSesTransactionEmailMapper(
        OrchestyPageEnum.SALES,
    );
    container.setCustomNode(hubspotToHubspotSalesTransactionEmail);

    const hubspotToHubspotContactTransactionEmail = new HubspotToSesTransactionEmailMapper(
        OrchestyPageEnum.CONTACT,
    );
    container.setCustomNode(hubspotToHubspotContactTransactionEmail);

    const hubspotToHubspotCommunityTransactionEmail = new HubspotToSesTransactionEmailMapper(
        OrchestyPageEnum.COMMUNITY,
    );
    container.setCustomNode(hubspotToHubspotCommunityTransactionEmail);

    const hubspotToHubspotNewsletterTransactionEmail = new HubspotToSesTransactionEmailMapper(
        OrchestyPageEnum.NEWSLETTER,
    );
    container.setCustomNode(hubspotToHubspotNewsletterTransactionEmail);

    const hubSpotSendTransactionEmailConnector = new HubSpotSendTransactionEmailConnector()
        .setSender(sender)
        .setApplication(hubspotAppBasic)
        .setDb(mongoDb);
    container.setConnector(hubSpotSendTransactionEmailConnector);
}
