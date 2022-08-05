import CoreServices from '@orchesty/nodejs-sdk/dist/lib/DIContainer/CoreServices';
import SlackApplication from '@orchesty/nodejs-connectors/dist/lib/Slack/SlackApplication';
import { initiateContainer, container } from '@orchesty/nodejs-sdk';
import DiscordApplication from '@orchesty/nodejs-connectors/dist/lib/Discord/DiscordApplication';
import TrelloApplication from '@orchesty/nodejs-connectors/dist/lib/Trello/TrelloApplication';
import AsanaApplication from '@orchesty/nodejs-connectors/dist/lib/Asana/AsanaApplication';
import BigcommerceApplication from '@orchesty/nodejs-connectors/dist/lib/Bigcommerce/BigcommerceApplication';
import FakturoidApplication from '@orchesty/nodejs-connectors/dist/lib/Fakturoid/FakturoidApplication';
import FlexiBeeApplication from '@orchesty/nodejs-connectors/dist/lib/FlexiBee/FexiBeeApplication';
import HubSpotApplication from '@orchesty/nodejs-connectors/dist/lib/Hubspot/HubSpotApplication';
import IDokladApplication from '@orchesty/nodejs-connectors/dist/lib/IDoklad/IDokladApplication';
import MailchimpApplication from '@orchesty/nodejs-connectors/dist/lib/Mailchimp/MailchimpApplication';
import NutshellApplication from '@orchesty/nodejs-connectors/dist/lib/Nutshell/NutshellApplication';
import PipedriveApplication from '@orchesty/nodejs-connectors/dist/lib/Pipedrive/PipedriveApplication';
import QuickbooksApplication from '@orchesty/nodejs-connectors/dist/lib/Quickbooks/QuickbooksApplication';
import SalesForceApplication from '@orchesty/nodejs-connectors/dist/lib/SalesForce/SalesForceApplication';
import SendGridApplication from '@orchesty/nodejs-connectors/dist/lib/SendGrid/SendGridApplication';
import ShipstationApplication from '@orchesty/nodejs-connectors/dist/lib/Shipstation/ShipstationApplication';
import WisepopsApplication from '@orchesty/nodejs-connectors/dist/lib/Wisepops/WisepopsApplication';
import ZendeskApplication from '@orchesty/nodejs-connectors/dist/lib/Zendesk/ZendeskApplication';
import ZohoApplication from '@orchesty/nodejs-connectors/dist/lib/Zoho/ZohoApplication';
import JiraApplication from '@orchesty/nodejs-connectors/dist/lib/Jira/JiraApplication';
import GoogleDriveApplication from '@orchesty/nodejs-connectors/dist/lib/Google/GoogleDrive/GoogleDriveApplication';
import DiscordSendMessageConnector
  from '@orchesty/nodejs-connectors/dist/lib/Discord/Connectors/DiscordSendMessageConnector';
import SlackSendMessageConnector from '@orchesty/nodejs-connectors/dist/lib/Slack/Connectors/SlackSendMessageConnector';
import AsanaCreateTaskConnector from '@orchesty/nodejs-connectors/dist/lib/Asana/Connectors/AsanaCreateTaskConnector';
import TrelloCreateCardConnector from
  '@orchesty/nodejs-connectors/dist/lib/Trello/Connectors/TrelloCreateCardConnector';
import JiraCreateIssueConnector from '@orchesty/nodejs-connectors/dist/lib/Jira/Connectors/JiraCreateIssueConnector';
import RDSApplication from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/RDS/RDSApplication';
import RDSAddRoleToDBCluster from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/RDS/Connector/RDSAddRoleToDBCluster';
import RedshiftApplication from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/Redshift/RedshiftApplication';
import RedshiftExecuteQueryConnector
  from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/Redshift/Connector/RedshiftExecuteQueryConnector';
import S3Application from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/S3/S3Application';
import DropboxApplication from '@orchesty/nodejs-connectors/dist/lib/Dropbox/DropboxApplication';
import FacebookAdsApplication from '@orchesty/nodejs-connectors/dist/lib/FacebookAds/FacebookAdsApplication';
import GoogleCalendarApplication
  from '@orchesty/nodejs-connectors/dist/lib/Google/GoogleCalendar/GoogleCalendarApplication';
import GoogleSheetApplication from '@orchesty/nodejs-connectors/dist/lib/Google/GoogleSheet/GoogleSheetApplication';
import YoutubeApplication from '@orchesty/nodejs-connectors/dist/lib/Google/Youtube/YoutubeApplication';
import StripeApplication from '@orchesty/nodejs-connectors/dist/lib/Stripe/StripeApplication';
import TwilioApplication from '@orchesty/nodejs-connectors/dist/lib/Twilio/TwilioApplication';
import WebflowApplication from '@orchesty/nodejs-connectors/dist/lib/Webflow/WebflowApplication';
import ZoomApplication from '@orchesty/nodejs-connectors/dist/lib/Zoom/ZoomApplication';
import TableauApplication from '@orchesty/nodejs-connectors/dist/lib/Tableau/TableauApplication';
import AirtableApplication from '@orchesty/nodejs-connectors/dist/lib/Airtable/AirtableApplication';
import Magento2Application from '@orchesty/nodejs-connectors/dist/lib/Magento2/Magento2Application';
import CacheService from '@orchesty/nodejs-sdk/dist/lib/Cache/CacheService';
import MoneyS5Application from '@orchesty/nodejs-connectors/dist/lib/MoneyS5/MoneyS5Application';
import ShoptetPremiumApplication from '@orchesty/nodejs-connectors/dist/lib/Shoptet/ShoptetPremiumApplication';
import ShopifyApplication from '@orchesty/nodejs-connectors/dist/lib/Shopify/ShopifyApplication';
import Redis from '@orchesty/nodejs-sdk/dist/lib/Storage/Redis/Redis';
import WooCommerceApplication from '@orchesty/nodejs-connectors/dist/lib/WooCommerce/WooCommerceApplication';
import UpgatesApplication from '@orchesty/nodejs-connectors/dist/lib/Upgates/UpgatesApplication';
import MySqlApplication from '@orchesty/nodejs-connectors/dist/lib/Sql/MySqlApplication';
import MsSqlApplication from '@orchesty/nodejs-connectors/dist/lib/Sql/MsSqlApplication';
import MariaDbApplication from '@orchesty/nodejs-connectors/dist/lib/Sql/MariaDbApplication';
import PostgreSqlApplication from '@orchesty/nodejs-connectors/dist/lib/Sql/PostgreSqlApplication';
import SqliteApplication from '@orchesty/nodejs-connectors/dist/lib/Sql/SqliteApplication';
import LambdaApplication from '@orchesty/nodejs-connectors/dist/lib/AmazonApps/Lambda/LambdaApplication';
import HubSpotCreateContactConnector
  from '@orchesty/nodejs-connectors/dist/lib/Hubspot/Connector/HubSpotCreateContactConnector';
import HubSpotCreateContactMapper from '@orchesty/nodejs-connectors/dist/lib/Hubspot/Mapper/HubSpotCreateContactMapper';
import ListPosts from './JsonPlaceholder/Batch/ListPosts';
import BinSender from './JsonPlaceholder/Connector/BinSender';
import Node from './JsonPlaceholder/Custom/Node';
import ListUsers from './JsonPlaceholder/Batch/ListUsers';
import TenantApplication from './JsonPlaceholder/TenantApplication';

export async function start(): Promise<void> {
  await initiateContainer();
  const sender = container.get(CoreServices.CURL);
  const mongoDb = container.get(CoreServices.MONGO);
  const provider = container.get(CoreServices.OAUTH2_PROVIDER);
  const runner = container.get(CoreServices.TOPOLOGY_RUNNER);
  const redis = new Redis('');
  const cache = new CacheService(redis, sender);

  const tenantApp = new TenantApplication();
  container.setApplication(tenantApp);

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

  const quickbooksApp = new QuickbooksApplication(provider);
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
    .setApplication(hubspotApp)
    .setDb(mongoDb);
  container.setConnector(hubspotCreateContact);

  const hubspotContactMapper = new HubSpotCreateContactMapper();
  hubspotContactMapper
    .setDb(mongoDb)
    .setApplication(hubspotApp);
  container.setCustomNode(hubspotContactMapper);

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

  const redShiftExecQuery = new RedshiftExecuteQueryConnector()
    .setSender(sender)
    .setApplication(awsRedshift)
    .setDb(mongoDb);
  container.setConnector(redShiftExecQuery);

  const node = new Node();
  container.setCustomNode(node);
}
