import AirtableApplication from '@orchesty/connector-airtable/dist/AirtableApplication';
import LambdaApplication from '@orchesty/connector-amazon-apps-lambda/dist/LambdaApplication';
import RDSAddRoleToDBCluster from '@orchesty/connector-amazon-apps-rds/dist/Connector/RDSAddRoleToDBCluster';
import RDSApplication from '@orchesty/connector-amazon-apps-rds/dist/RDSApplication';
import RedshiftExecuteQueryConnector
    from '@orchesty/connector-amazon-apps-redshift/dist/Connector/RedshiftExecuteQueryConnector';
import RedshiftApplication from '@orchesty/connector-amazon-apps-redshift/dist/RedshiftApplication';
import S3Application from '@orchesty/connector-amazon-apps-s3/dist/S3Application';
import SESSendEmail from '@orchesty/connector-amazon-apps-simple-email-service/dist/Connector/SESSendEmail';
import AsanaApplication from '@orchesty/connector-asana/dist/AsanaApplication';
import AsanaCreateTaskConnector from '@orchesty/connector-asana/dist/Connector/AsanaCreateTaskConnector';
import BigcommerceApplication from '@orchesty/connector-bigcommerce/dist/BigcommerceApplication';
import BoxApplication from '@orchesty/connector-box/dist/BoxApplication';
import { EventEnum } from '@orchesty/connector-common/dist/Events/EventEnum';
import EventStatusFilter from '@orchesty/connector-common/dist/EventStatusFilter/EventStatusFilter';
import ListUsersCommon from '@orchesty/connector-common/dist/ListUsers/ListUsers';
import GetApplicationForRefreshBatchConnector
    from '@orchesty/connector-common/dist/OAuth2/GetApplicationForRefreshBatchConnector';
import RefreshOAuth2TokenNode from '@orchesty/connector-common/dist/OAuth2/RefreshOAuth2TokenNode';
import DiscordSendMessageConnector
    from '@orchesty/connector-discord/dist/Connector/DiscordSendMessageConnector';
import DiscordApplication from '@orchesty/connector-discord/dist/DiscordApplication';
import DropboxApplication from '@orchesty/connector-dropbox/dist/DropboxApplication';
import FacebookAdsApplication from '@orchesty/connector-facebook-ads/dist/FacebookAdsApplication';
import FakturoidApplication from '@orchesty/connector-fakturoid/dist/FakturoidApplication';
import FlexiBeeCleneniDphBatch from '@orchesty/connector-flexi-bee/dist/Batch/FlexiBeeCleneniDphBatch';
import FlexiBeeCleneniKontrolniHlaseniBatch from '@orchesty/connector-flexi-bee/dist/Batch/FlexiBeeCleneniKontrolniHlaseniBatch';
import FlexiBeePredpisZauctovaniBatch from '@orchesty/connector-flexi-bee/dist/Batch/FlexiBeePredpisZauctovaniBatch';
import FlexiBeeStrediskoBatch from '@orchesty/connector-flexi-bee/dist/Batch/FlexiBeeStrediskoBatch';
import FlexiBeeTypFakturyPrijateBatch from '@orchesty/connector-flexi-bee/dist/Batch/FlexiBeeTypFakturyPrijateBatch';
import FlexiBeeUcetBatch from '@orchesty/connector-flexi-bee/dist/Batch/FlexiBeeUcetBatch';
import FlexiBeeZakazkaBatch from '@orchesty/connector-flexi-bee/dist/Batch/FlexiBeeZakazkaBatch';
import FlexiBeeCreateFakturaPrijataConnector from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeCreateFakturaPrijataConnector';
import FlexiBeeCreateFakturaPrijataPrilohaConnector from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeCreateFakturaPrijataPrilohaConnector';
import FlexiBeeCreateZavazekConnector from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeCreateZavazekConnector';
import FlexiBeeCreateZavazekPrilohaConnector from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeCreateZavazekPrilohaConnector';
import FlexiBeeGetCompaniesConnector from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeGetCompaniesConnector';
import FlexiBeeGetFakturaPrijataConnector from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeGetFakturaPrijataConnector';
import FlexiBeeGetZavazekConnector from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeGetZavazekConnector';
import GitHubGetRepositoryConnector from '@orchesty/connector-git-hub/dist/Connector/GitHubGetRepositoryConnector';
import GitHubApplication from '@orchesty/connector-git-hub/dist/GitHubApplication';
import GoogleCalendarApplication
    from '@orchesty/connector-google-calendar/dist/GoogleCalendarApplication';
import GoogleDriveApplication from '@orchesty/connector-google-drive/dist/GoogleDriveApplication';
import GoogleDriveUploadFileConnector
    from '@orchesty/connector-google-sheet/dist/Connector/GoogleSheetCreateSpreadsheetConnector';
import YoutubeApplication from '@orchesty/connector-google-youtube/dist/YoutubeApplication';
import HubSpotSendTransactionEmailConnector
    from '@orchesty/connector-hubspot/dist/Connector/HubSpotSendTransactionEmailConnector';
import HubSpotApplication from '@orchesty/connector-hubspot/dist/HubSpotApplication';
import HubSpotApplicationBasic from '@orchesty/connector-hubspot/dist/HubSpotApplicationBasic';
import IDokladApplication from '@orchesty/connector-idoklad/dist/IDokladApplication';
import JiraCreateIssueConnector from '@orchesty/connector-jira/dist/Connector/JiraCreateIssueConnector';
import JiraApplication from '@orchesty/connector-jira/dist/JiraApplication';
import JsonPlaceholderApplication from '@orchesty/connector-json-placeholder/dist/JsonPlaceholderApplication';
import Magento2Application from '@orchesty/connector-magento2/dist/Magento2Application';
import MailchimpApplication from '@orchesty/connector-mailchimp/dist/MailchimpApplication';
import MoneyS5Application from '@orchesty/connector-moneys4-5/dist/MoneyS5Application';
import NutshellApplication from '@orchesty/connector-nutshell/dist/NutshellApplication';
import PipedriveApplication from '@orchesty/connector-pipedrive/dist/PipedriveApplication';
import QuickBooksApplication from '@orchesty/connector-quick-books/dist/QuickBooksApplication';
import SalesForceApplication from '@orchesty/connector-sales-force/dist/SalesForceApplication';
import SendGridApplication from '@orchesty/connector-send-grid/dist/SendGridApplication';
import ShipstationApplication from '@orchesty/connector-shipstation/dist/ShipstationApplication';
import ShopifyApplication from '@orchesty/connector-shopify/dist/ShopifyApplication';
import ShoptetPremiumApplication from '@orchesty/connector-shoptet/dist/ShoptetPremiumApplication';
import SlackSendMessageConnector from '@orchesty/connector-slack/dist/Connectors/SlackSendMessageConnector';
import SlackApplication from '@orchesty/connector-slack/dist/SlackApplication';
import MariaDbApplication from '@orchesty/connector-sql/dist/MariaDbApplication';
import MsSqlApplication from '@orchesty/connector-sql/dist/MsSqlApplication';
import MySqlApplication from '@orchesty/connector-sql/dist/MySqlApplication';
import OracleDbApplication from '@orchesty/connector-sql/dist/OracleDbApplication';
import PostgreSqlApplication from '@orchesty/connector-sql/dist/PostgreSqlApplication';
import SqliteApplication from '@orchesty/connector-sql/dist/SqliteApplication';
import StripeApplication from '@orchesty/connector-stripe/dist/StripeApplication';
import TableauApplication from '@orchesty/connector-tableau/dist/TableauApplication';
import TrelloCreateCardConnector from '@orchesty/connector-trello/dist/Connector/TrelloCreateCardConnector';
import TrelloApplication from '@orchesty/connector-trello/dist/TrelloApplication';
import TwilioApplication from '@orchesty/connector-twilio/dist/TwilioApplication';
import UpgatesApplication from '@orchesty/connector-upgates/dist/UpgatesApplication';
import WebflowApplication from '@orchesty/connector-webflow/dist/WebflowApplication';
import WflowSubscribeWebhookBatch from '@orchesty/connector-wflow/dist/Batch/WflowSubscribeWebhookBatch';
import WflowUnsubscribeWebhookBatch from '@orchesty/connector-wflow/dist/Batch/WflowUnsubscribeWebhookBatch';
import WflowGetDocumentConnector from '@orchesty/connector-wflow/dist/Connector/WflowGetDocumentConnector';
import WflowGetDocumentMainFileConnector from '@orchesty/connector-wflow/dist/Connector/WflowGetDocumentMainFileConnector';
import WflowGetDocumentTypesConnector from '@orchesty/connector-wflow/dist/Connector/WflowGetDocumentTypesConnector';
import WflowGetOrganizationsConnector from '@orchesty/connector-wflow/dist/Connector/WflowGetOrganizationsConnector';
import WflowPatchAccountingRulesConnector from '@orchesty/connector-wflow/dist/Connector/WflowPatchAccountingRulesConnector';
import WflowPatchChartOfAccountsConnector from '@orchesty/connector-wflow/dist/Connector/WflowPatchChartOfAccountsConnector';
import WflowPatchContractsConnector from '@orchesty/connector-wflow/dist/Connector/WflowPatchContractsConnector';
import WflowPatchCostCentersConnector from '@orchesty/connector-wflow/dist/Connector/WflowPatchCostCentersConnector';
import WflowPatchSeriesConnector from '@orchesty/connector-wflow/dist/Connector/WflowPatchSeriesConnector';
import WflowPatchVatControlStatementLinesConnector from '@orchesty/connector-wflow/dist/Connector/WflowPatchVatControlStatementLinesConnector';
import WflowPatchVatReturnLinesConnector from '@orchesty/connector-wflow/dist/Connector/WflowPatchVatReturnLinesConnector';
import WflowPutDocumentConnector from '@orchesty/connector-wflow/dist/Connector/WflowPutDocumentConnector';
import WflowUpdateDocumentStateConnector from '@orchesty/connector-wflow/dist/Connector/WflowUpdateDocumentStateConnector';
import WisepopsApplication from '@orchesty/connector-wisepops/dist/WisepopsApplication';
import WooCommerceApplication from '@orchesty/connector-woocommerce/dist/WooCommerceApplication';
import XeroApplication from '@orchesty/connector-xero/dist/XeroApplication';
import ZendeskApplication from '@orchesty/connector-zendesk/dist/ZendeskApplication';
import ZohoApplication from '@orchesty/connector-zoho/dist/ZohoApplication';
import ZoomApplication from '@orchesty/connector-zoom/dist/ZoomApplication';
import { container, initiateContainer } from '@orchesty/nodejs-sdk';
import { OAuth2Provider } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Provider/OAuth2/OAuth2Provider';
import CacheService from '@orchesty/nodejs-sdk/dist/lib/Cache/CacheService';
import { getEnv } from '@orchesty/nodejs-sdk/dist/lib/Config/Config';
import DatabaseClient from '@orchesty/nodejs-sdk/dist/lib/Storage/Database/Client';
import DataStorageManager from '@orchesty/nodejs-sdk/dist/lib/Storage/DataStore/DataStorageManager';
import FileSystem from '@orchesty/nodejs-sdk/dist/lib/Storage/File/FileSystem';
import { MongoDb } from '@orchesty/nodejs-sdk/dist/lib/Storage/Mongo';
import Redis from '@orchesty/nodejs-sdk/dist/lib/Storage/Redis/Redis';
import TopologyRunner from '@orchesty/nodejs-sdk/dist/lib/Topology/TopologyRunner';
import CurlSender from '@orchesty/nodejs-sdk/dist/lib/Transport/Curl/CurlSender';
import HubspotApplinthContactAddContactToListMapper
    from './ApplinthIo/CustomNode/HubspotApplinthContactAddContactToListMapper';
import HubspotApplinthWhitePaperAddContactToListMapper
    from './ApplinthIo/CustomNode/HubspotApplinthWhitePaperAddContactToListMapper';
import HubspotWhiterPaperToSesEmailMapper from './ApplinthIo/CustomNode/HubspotWhiterPaperToSesEmailMapper';
import BeeceptorCreateWebhooks from './Beeceptor/Batch/BeeceptorCreateWebhooks';
import BeeceptorDeleteWebhooks from './Beeceptor/Batch/BeeceptorDeleteWebhooks';
import BeeceptorApplication from './Beeceptor/BeeceptorApplication';
import BeeceptorPostCategoryConnector from './Beeceptor/Connector/BeeceptorPostCategoryConnector';
import BeeceptorPostProductConnector from './Beeceptor/Connector/BeeceptorPostProductConnector';
import BeeceptorPutCategoryConnector from './Beeceptor/Connector/BeeceptorPutCategoryConnector';
import BeeceptorPutProductCategoriesConnector from './Beeceptor/Connector/BeeceptorPutProductCategoriesConnector';
import BeeceptorPutProductConnector from './Beeceptor/Connector/BeeceptorPutProductConnector';
import BeeceptorSyncPostConnector from './Beeceptor/Connector/BeeceptorSyncPostConnector';
import HubSpotAddEmailToListConnector from './Common/Connector/HubSpotAddEmailToListConnector';
import HubSpotCreateContactConnector from './Common/Connector/HubSpotCreateContactConnector';
import HanabosoHubSpotContactMapper from './Common/CustomNode/HanabosoHubSpotContactMapper';
import HanabosoToJiraMapper from './Common/CustomNode/HanabosoToJiraMapper';
import HubspotAddContactToListMapper from './Common/CustomNode/HubspotAddContactToListMapper';
import HubspotToSesTransactionEmailMapper from './Common/CustomNode/HubspotToSesEmailMapper';
import { HubspotListIdsEnums } from './Common/Enum/HubspotListIdsEnums';
import { PageEnum } from './Common/Enum/PageEnum';
import SESApplication from './Common/SESApplication';
import FlexiBeeFindFirmaKodConnector from './FlexiBee/Connector/FlexiBeeFindFirmaKodConnector';
import FlexiBeeCleneniDphToWflowVatReturnLinesMapper from './FlexiBee/CustomNode/FlexiBeeCleneniDphToWflowVatReturnLinesMapper';
import FlexiBeeCleneniKontrolniHlaseniToWflowVatControlStatementLinesMapper from './FlexiBee/CustomNode/FlexiBeeCleneniKontrolniHlaseniToWflowVatControlStatementLinesMapper';
import FlexiBeeCreateFakturaPrijataToFlexiBeeGetFakturaPrijataMapper from './FlexiBee/CustomNode/FlexiBeeCreateFakturaPrijataToFlexiBeeGetFakturaPrijataMapper';
import FlexiBeeCreateZavazekToFlexiBeeGetZavazekMapper from './FlexiBee/CustomNode/FlexiBeeCreateZavazekToFlexiBeeGetZavazekMapper';
import FlexiBeeFakturaPrijataToWflowDocumentMapper from './FlexiBee/CustomNode/FlexiBeeFakturaPrijataToWflowDocumentMapper';
import FlexiBeePredpisZauctovaniToWflowAccountingRulesMapper from './FlexiBee/CustomNode/FlexiBeePredpisZauctovaniToWflowAccountingRulesMapper';
import FlexiBeeStrediskoToWflowCostCentersMapper from './FlexiBee/CustomNode/FlexiBeeStrediskoToWflowCostCentersMapper';
import FlexiBeeTypFakturyPrijateToWflowSeriesMapper from './FlexiBee/CustomNode/FlexiBeeTypFakturyPrijateToWflowSeriesMapper';
import FlexiBeeUcetToWflowChartOfAccountsMapper from './FlexiBee/CustomNode/FlexiBeeUcetToWflowChartOfAccountsMapper';
import FlexiBeeZakazkaToWflowContractsMapper from './FlexiBee/CustomNode/FlexiBeeZakazkaToWflowContractsMapper';
import { FlexiBeeApplication } from './FlexiBee/FlexiBeeApplication';
import GoogleDriveCreateDirectoryConnector from './Google/GoogleDrive/Connector/GoogleDriveCreateDirectoryConnector';
import GoogleDriveUpdateFileConnector from './Google/GoogleDrive/Connector/GoogleDriveUpdateFileConnector';
import GoogleSheetApplication from './Google/GoogleSheet/GoogleSheetApplication';
import JiraGetIssueBatch from './Hanaboso/Batch/JiraGetIssueBatch';
import JiraGetUpdatedWorklogIdsBatch from './Hanaboso/Batch/JiraGetUpdatedWorklogIdsBatch';
import JiraGetWorklogsBatch from './Hanaboso/Batch/JiraGetWorklogsBatch';
import JiraSortWorklogsByProjectsBatch from './Hanaboso/Batch/JiraSortWorklogsByProjectsBatch';
import GoogleSheetGetSpreadsheet from './Hanaboso/Connector/GoogleSheetGetSpreadsheet';
import GoogleSheetUpdateBatchSpreadsheet from './Hanaboso/Connector/GoogleSheetUpdateBatchSpreadsheet';
import JiraWorklogGoogleDriveMapper from './Hanaboso/CustomNode/JiraWorklogGoogleDriveMapper';
import JiraWorklogsToGoogleDriveMapper from './Hanaboso/CustomNode/JiraWorklogsToGoogleDriveMapper';
import SetupGoogleSheetSettingDirectory from './Hanaboso/CustomNode/SetupGoogleSheetSettingDirectory';
import SetupGoogleSheetSettingSpreadsheet from './Hanaboso/CustomNode/SetupGoogleSheetSettingSpreadsheet';
import HanabosoContactFormMapper from './HanabosoCom/CustomNode/ContactFormMapper';
import JsonPlaceholderGetPostCommentListBatch from './JsonPlaceholder/Batch/JsonPlaceholderGetPostCommentListBatch';
import JsonPlaceholderGetPostListBatch from './JsonPlaceholder/Batch/JsonPlaceholderGetPostListBatch';
import BinSender from './JsonPlaceholder/Connector/BinSender';
import GetPost from './JsonPlaceholder/Connector/GetPost';
import JsonPlaceholderGetPostUserConnector from './JsonPlaceholder/Connector/JsonPlaceholderGetPostUserConnector';
import JsonPlaceholderToBeeceptorSyncPostMapper from './JsonPlaceholder/Custom/JsonPlaceholderToBeeceptorSyncPostMapper';
import Node from './JsonPlaceholder/Custom/Node';
import SleepAndStop from './JsonPlaceholder/Custom/SleepAndStop';
import HubSpotCreateContactMapper from './JsonPlaceholder/HubSpotCreateContactMapper';
import NonInstallableApplication from './JsonPlaceholder/NonInstallableApplication';
import SampleApplication from './JsonPlaceholder/SampleApplication';
import TenantApplication from './JsonPlaceholder/TenantApplication';
import MySqlGetCategoryListBatch from './Sql/Batch/MySqlGetCategoryListBatch';
import MySqlGetProductCategoryListBatch from './Sql/Batch/MySqlGetProductCategoryListBatch';
import MySqlGetProductListBatch from './Sql/Batch/MySqlGetProductListBatch';
import MySqlCategoryFindId from './Sql/CustomNode/MySqlCategoryFindId';
import MySqlCategoryStoreId from './Sql/CustomNode/MySqlCategoryStoreId';
import MySqlProductCategoryFindId from './Sql/CustomNode/MySqlProductCategoryFindId';
import MySqlProductFindId from './Sql/CustomNode/MySqlProductFindId';
import MySqlProductStoreId from './Sql/CustomNode/MySqlProductStoreId';
import MySqlRepository from './Sql/Repository/MySqlRepository';
import WflowDocumentMainFileToFlexiBeeFakturaPrijataPrilohaMapper from './Wflow/CustomNode/WflowDocumentMainFileToFlexiBeeFakturaPrijataPrilohaMapper';
import WflowDocumentToFlexibeeFakturaPrijataMapper from './Wflow/CustomNode/WflowDocumentToFlexibeeFakturaPrijataMapper';
import WflowWebhookPayloadMapper from './Wflow/CustomNode/WflowWebhookPayloadMapper';
import WflowApplication from './Wflow/WflowApplication';

export async function start(): Promise<void> {
    initiateContainer();
    const sender = container.get(CurlSender);
    const mongoDb = container.get(DatabaseClient);
    const provider = container.get(OAuth2Provider);
    const etl = new DataStorageManager(new FileSystem());
    const redis = new Redis('');
    const cache = new CacheService(redis, sender);
    const runner = container.get(TopologyRunner);

    container.set(new MongoDb(getEnv('MONGO_DSN')));

    container.set(new MySqlRepository(container.get(MongoDb), 'MySqlDocument'));
    await container.get(MySqlRepository).createIndices();

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

    const flexiBeeGetCompaniesConnector = new FlexiBeeGetCompaniesConnector(true);
    container.setNode(flexiBeeGetCompaniesConnector, flexiBeeApp);
    container.setNode(new FlexiBeeCreateFakturaPrijataConnector(), flexiBeeApp);
    container.setNode(new FlexiBeeCreateFakturaPrijataPrilohaConnector(), flexiBeeApp);
    container.setNode(new FlexiBeeFindFirmaKodConnector(), flexiBeeApp);
    container.setNode(new FlexiBeeGetFakturaPrijataConnector(), flexiBeeApp);
    container.setNode(new FlexiBeeCreateZavazekConnector(), flexiBeeApp);
    container.setNode(new FlexiBeeCreateZavazekPrilohaConnector(), flexiBeeApp);
    container.setNode(new FlexiBeeGetZavazekConnector(), flexiBeeApp);
    container.setNode(new FlexiBeeCreateFakturaPrijataToFlexiBeeGetFakturaPrijataMapper(), flexiBeeApp);
    container.setNode(new FlexiBeeCreateZavazekToFlexiBeeGetZavazekMapper(), flexiBeeApp);
    container.setNode(new FlexiBeeFakturaPrijataToWflowDocumentMapper(), flexiBeeApp);
    container.setNode(new FlexiBeeStrediskoBatch(), flexiBeeApp);
    container.setNode(new FlexiBeeZakazkaBatch(), flexiBeeApp);
    container.setNode(new FlexiBeeUcetBatch(), flexiBeeApp);
    container.setNode(new FlexiBeePredpisZauctovaniBatch(), flexiBeeApp);
    container.setNode(new FlexiBeeCleneniDphBatch(), flexiBeeApp);
    container.setNode(new FlexiBeeCleneniKontrolniHlaseniBatch(), flexiBeeApp);
    container.setNode(new FlexiBeeTypFakturyPrijateBatch(), flexiBeeApp);
    container.setNode(new FlexiBeeStrediskoToWflowCostCentersMapper(), flexiBeeApp);
    container.setNode(new FlexiBeeZakazkaToWflowContractsMapper(), flexiBeeApp);
    container.setNode(new FlexiBeeUcetToWflowChartOfAccountsMapper(), flexiBeeApp);
    container.setNode(new FlexiBeePredpisZauctovaniToWflowAccountingRulesMapper(), flexiBeeApp);
    container.setNode(new FlexiBeeCleneniDphToWflowVatReturnLinesMapper(), flexiBeeApp);
    container.setNode(new FlexiBeeCleneniKontrolniHlaseniToWflowVatControlStatementLinesMapper(), flexiBeeApp);
    container.setNode(new FlexiBeeTypFakturyPrijateToWflowSeriesMapper(), flexiBeeApp);

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

    const shoptetPremApp = new ShoptetPremiumApplication();
    container.setApplication(shoptetPremApp);

    const shopifyApp = new ShopifyApplication(sender, provider);
    container.setApplication(shopifyApp);

    const nutshellApp = new NutshellApplication();
    container.setApplication(nutshellApp);

    const pipedriveApp = new PipedriveApplication();
    container.setApplication(pipedriveApp);

    const quickbooksApp = new QuickBooksApplication(provider, mongoDb, sender);
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

    const githubApplication = new GitHubApplication();
    container.setApplication(githubApplication);
    container.setNode(new GitHubGetRepositoryConnector(), githubApplication);

    const beeceptorApplication = new BeeceptorApplication(container.get(TopologyRunner));
    container.setApplication(beeceptorApplication);
    container.setNode(new BeeceptorSyncPostConnector(), beeceptorApplication);
    container.setNode(new BeeceptorPostCategoryConnector(), beeceptorApplication);
    container.setNode(new BeeceptorPutCategoryConnector(), beeceptorApplication);
    container.setNode(new BeeceptorPostProductConnector(), beeceptorApplication);
    container.setNode(new BeeceptorPutProductConnector(), beeceptorApplication);
    container.setNode(new BeeceptorPutProductCategoriesConnector(), beeceptorApplication);
    container.setNode(new BeeceptorCreateWebhooks(), beeceptorApplication);
    container.setNode(new BeeceptorDeleteWebhooks(), beeceptorApplication);

    const wflowGetOrganizationsConnector = new WflowGetOrganizationsConnector(true).setSender(sender).setDb(mongoDb);
    const wflowGetDocumentTypesConnector = new WflowGetDocumentTypesConnector(true).setSender(sender).setDb(mongoDb);
    const wflowApplication = new WflowApplication(
        provider,
        wflowGetOrganizationsConnector,
        wflowGetDocumentTypesConnector,
        flexiBeeGetCompaniesConnector,
        runner,
    );
    wflowGetOrganizationsConnector.setApplication(wflowApplication);
    wflowGetDocumentTypesConnector.setApplication(wflowApplication);
    container.setApplication(wflowApplication);
    container.setNode(new WflowSubscribeWebhookBatch(), wflowApplication);
    container.setNode(new WflowUnsubscribeWebhookBatch(), wflowApplication);
    container.setNode(new WflowWebhookPayloadMapper(), wflowApplication);
    container.setNode(new WflowDocumentToFlexibeeFakturaPrijataMapper(), wflowApplication);
    container.setNode(new WflowGetDocumentConnector(), wflowApplication);
    container.setNode(new WflowGetDocumentMainFileConnector(), wflowApplication);
    container.setNode(new WflowDocumentMainFileToFlexiBeeFakturaPrijataPrilohaMapper(), wflowApplication);
    container.setNode(new WflowPutDocumentConnector(), wflowApplication);
    container.setNode(new WflowUpdateDocumentStateConnector(), wflowApplication);
    container.setNode(new WflowPatchCostCentersConnector(), wflowApplication);
    container.setNode(new WflowPatchContractsConnector(), wflowApplication);
    container.setNode(new WflowPatchChartOfAccountsConnector(), wflowApplication);
    container.setNode(new WflowPatchAccountingRulesConnector(), wflowApplication);
    container.setNode(new WflowPatchVatReturnLinesConnector(), wflowApplication);
    container.setNode(new WflowPatchVatControlStatementLinesConnector(), wflowApplication);
    container.setNode(new WflowPatchSeriesConnector(), wflowApplication);

    const jsonPlaceholderApplication = new JsonPlaceholderApplication();
    container.setNode(new JsonPlaceholderGetPostListBatch(), jsonPlaceholderApplication);
    container.setNode(new JsonPlaceholderGetPostCommentListBatch(), jsonPlaceholderApplication);
    container.setNode(new JsonPlaceholderGetPostUserConnector(), jsonPlaceholderApplication);
    container.setNode(new JsonPlaceholderToBeeceptorSyncPostMapper());

    container.setBatch(new MySqlGetCategoryListBatch().setApplication(mysqlApp).setDb(mongoDb));
    container.setBatch(new MySqlGetProductListBatch().setApplication(mysqlApp).setDb(mongoDb));
    container.setBatch(new MySqlGetProductCategoryListBatch().setApplication(mysqlApp).setDb(mongoDb));

    container.setNode(new MySqlCategoryFindId(container.get(MySqlRepository)), mysqlApp);
    container.setNode(new MySqlProductFindId(container.get(MySqlRepository)), mysqlApp);
    container.setNode(new MySqlCategoryStoreId(container.get(MySqlRepository)), mysqlApp);
    container.setNode(new MySqlProductStoreId(container.get(MySqlRepository)), mysqlApp);
    container.setNode(new MySqlProductCategoryFindId(container.get(MySqlRepository)), mysqlApp);

    const getPost = new GetPost();
    getPost.setSender(sender);
    container.setConnector(getPost);

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

    const hubSpotContactMapper = new HanabosoHubSpotContactMapper();
    container.setCustomNode(hubSpotContactMapper);

    const hubSpotAddEmailToListConnector = new HubSpotAddEmailToListConnector()
        .setSender(sender)
        .setApplication(hubspotAppBasic)
        .setDb(mongoDb);
    container.setConnector(hubSpotAddEmailToListConnector);

    const addContactToHubspotSalesListMapper = new HubspotAddContactToListMapper(HubspotListIdsEnums.SALES);
    container.setCustomNode(addContactToHubspotSalesListMapper);

    const addContactToHubspotContactListMapper = new HubspotAddContactToListMapper(HubspotListIdsEnums.CONTACT_FROM);
    container.setCustomNode(addContactToHubspotContactListMapper);

    const addContactToHubspotCommunityListMapper = new HubspotAddContactToListMapper(HubspotListIdsEnums.COMMUNITY);
    container.setCustomNode(addContactToHubspotCommunityListMapper);

    const addContactToHubspotNewsletterListMapper = new HubspotAddContactToListMapper(HubspotListIdsEnums.NEWSLETTER);
    container.setCustomNode(addContactToHubspotNewsletterListMapper);

    const hubspotApplinthAddContactToListMapper = new HubspotApplinthContactAddContactToListMapper();
    container.setCustomNode(hubspotApplinthAddContactToListMapper);

    const hubspotApplinthWhitePaperAddContactToListMapper = new HubspotApplinthWhitePaperAddContactToListMapper();
    container.setCustomNode(hubspotApplinthWhitePaperAddContactToListMapper);

    const hubspotToJiraSalesMapper = new HanabosoToJiraMapper(PageEnum.SALES, ['sales']);
    container.setCustomNode(hubspotToJiraSalesMapper);

    const hubspotToJiraContactMapper = new HanabosoToJiraMapper(PageEnum.CONTACT, ['contact']);
    container.setCustomNode(hubspotToJiraContactMapper);

    const hubspotToJiraCommunityMapper = new HanabosoToJiraMapper(PageEnum.COMMUNITY, ['community']);
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

    const jiraGetWorklogsBatch = new JiraGetWorklogsBatch(etl)
        .setSender(sender)
        .setApplication(jiraApp)
        .setDb(mongoDb);
    container.setBatch(jiraGetWorklogsBatch);

    const jiraGetIssueBatch = new JiraGetIssueBatch(etl)
        .setSender(sender)
        .setApplication(jiraApp)
        .setDb(mongoDb);
    container.setBatch(jiraGetIssueBatch);

    const jiraGetUpdatedWorklogsIds = new JiraGetUpdatedWorklogIdsBatch(etl)
        .setSender(sender)
        .setApplication(jiraApp)
        .setDb(mongoDb);
    container.setBatch(jiraGetUpdatedWorklogsIds);

    const jiraSortWorklogsByProjectsBatch = new JiraSortWorklogsByProjectsBatch(etl)
        .setSender(sender)
        .setApplication(jiraApp)
        .setDb(mongoDb);
    container.setBatch(jiraSortWorklogsByProjectsBatch);

    const jiraWorklogGoogleDriveMapper = new JiraWorklogGoogleDriveMapper();
    container.setCustomNode(jiraWorklogGoogleDriveMapper);

    const jiraWorklogsToGoogleDriveMapper = new JiraWorklogsToGoogleDriveMapper(etl)
        .setApplication(jiraApp)
        .setDb(mongoDb);
    container.setCustomNode(jiraWorklogsToGoogleDriveMapper);

    const setupGoogleSheetSettingDirectory = new SetupGoogleSheetSettingDirectory()
        .setApplication(googleSheetApp)
        .setDb(mongoDb);
    container.setCustomNode(setupGoogleSheetSettingDirectory);

    const setupGoogleSheetSettingSpreadsheet = new SetupGoogleSheetSettingSpreadsheet()
        .setApplication(googleSheetApp)
        .setDb(mongoDb);
    container.setCustomNode(setupGoogleSheetSettingSpreadsheet);

    const googleDriveCreateDirectoryConnector = new GoogleDriveCreateDirectoryConnector()
        .setSender(sender)
        .setApplication(googleDriveApp)
        .setDb(mongoDb);
    container.setConnector(googleDriveCreateDirectoryConnector);

    const googleDriveUpdateFileConnector = new GoogleDriveUpdateFileConnector()
        .setSender(sender)
        .setApplication(googleDriveApp)
        .setDb(mongoDb);
    container.setConnector(googleDriveUpdateFileConnector);

    const googleSheetCreateSpreadsheet = new GoogleDriveUploadFileConnector()
        .setSender(sender)
        .setApplication(googleSheetApp)
        .setDb(mongoDb);
    container.setConnector(googleSheetCreateSpreadsheet);

    const googleSheetGetSpreadsheet = new GoogleSheetGetSpreadsheet(etl)
        .setSender(sender)
        .setApplication(googleSheetApp)
        .setDb(mongoDb);
    container.setConnector(googleSheetGetSpreadsheet);

    const googleSheetUpdateBatchSpreadsheet = new GoogleSheetUpdateBatchSpreadsheet(etl)
        .setSender(sender)
        .setApplication(googleSheetApp)
        .setDb(mongoDb);
    container.setConnector(googleSheetUpdateBatchSpreadsheet);

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

    const sleep = new SleepAndStop();
    container.setCustomNode(sleep);

    const xeroApplication = new XeroApplication(provider, mongoDb, sender);
    container.setApplication(xeroApplication);

    const oracleDbApplication = new OracleDbApplication();
    container.setApplication(oracleDbApplication);

    const boxApplication = new BoxApplication(provider);
    container.setApplication(boxApplication);

    const listUsersCommon = new ListUsersCommon()
        .setApplication(sampleApp)
        .setDb(mongoDb);
    container.setBatch(listUsersCommon);

    const jiraListUsers = new ListUsersCommon()
        .setApplication(jiraApp)
        .setDb(mongoDb);
    container.setBatch(jiraListUsers);

    const hubspotToHubspotSalesTransactionEmail = new HubspotToSesTransactionEmailMapper(
        PageEnum.SALES,
    );
    container.setCustomNode(hubspotToHubspotSalesTransactionEmail);

    const hubspotToHubspotContactTransactionEmail = new HubspotToSesTransactionEmailMapper(
        PageEnum.CONTACT,
    );
    container.setCustomNode(hubspotToHubspotContactTransactionEmail);

    const hubspotToHubspotCommunityTransactionEmail = new HubspotToSesTransactionEmailMapper(
        PageEnum.COMMUNITY,
    );
    container.setCustomNode(hubspotToHubspotCommunityTransactionEmail);

    const hubspotToHubspotNewsletterTransactionEmail = new HubspotToSesTransactionEmailMapper(
        PageEnum.NEWSLETTER,
    );
    container.setCustomNode(hubspotToHubspotNewsletterTransactionEmail);

    const hubspotWhiterPaperToSesEmailMapper = new HubspotWhiterPaperToSesEmailMapper(PageEnum.WHITE_PAPER);
    container.setCustomNode(hubspotWhiterPaperToSesEmailMapper);

    const hanabosoContactFormEmail = new HanabosoContactFormMapper();
    container.setCustomNode(hanabosoContactFormEmail);

    const hubSpotSendTransactionEmailConnector = new HubSpotSendTransactionEmailConnector()
        .setSender(sender)
        .setApplication(hubspotAppBasic)
        .setDb(mongoDb);
    container.setConnector(hubSpotSendTransactionEmailConnector);

    const getApplicationForRefreshBatchConnector = new GetApplicationForRefreshBatchConnector()
        .setDb(mongoDb);
    container.setBatch(getApplicationForRefreshBatchConnector);

    const refreshOAuth2TokenNode = new RefreshOAuth2TokenNode(container)
        .setDb(mongoDb);
    container.setConnector(refreshOAuth2TokenNode);
}
