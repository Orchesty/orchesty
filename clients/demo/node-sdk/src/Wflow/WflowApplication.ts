import FlexiBeeGetCompaniesConnector, { IOutput as IFlexiBeeGetCompaniesConnectorOutput } from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeGetCompaniesConnector';
import { FLEXI_BEE_APPLICATION as FLEXI_BEE_NAME } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import WflowGetDocumentTypesConnector, { IOutput as IWflowGetDocumentTypesConnectorOutput } from '@orchesty/connector-wflow/dist/Connector/WflowGetDocumentTypesConnector';
import WflowGetOrganizationsConnector from '@orchesty/connector-wflow/dist/Connector/WflowGetOrganizationsConnector';
import WflowApplicationBase, { NAME as WFLOW_NAME, ORGANIZATION, ORGANIZATION_FORM, WebhookType } from '@orchesty/connector-wflow/dist/WflowApplication';
import { ApplicationInstall } from '@orchesty/nodejs-sdk/dist/lib/Application/Database/ApplicationInstall';
import Field from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Field';
import FieldType from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FieldType';
import Form from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/Form';
import FormStack from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Form/FormStack';
import WebhookSubscription from '@orchesty/nodejs-sdk/dist/lib/Application/Model/Webhook/WebhookSubscription';
import { OAuth2Provider } from '@orchesty/nodejs-sdk/dist/lib/Authorization/Provider/OAuth2/OAuth2Provider';
import TopologyRunner from '@orchesty/nodejs-sdk/dist/lib/Topology/TopologyRunner';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { Topology } from './Enum/Topology';

export const FLEXI_BEE_FORM = 'flexiBeeForm';

export default class WflowApplication extends WflowApplicationBase {

    public constructor(
        provider: OAuth2Provider,
        private readonly customWflowGetOrganizationsConnector: WflowGetOrganizationsConnector,
        private readonly wflowGetDocumentTypesConnector: WflowGetDocumentTypesConnector,
        private readonly flexiBeeGetCompaniesConnector: FlexiBeeGetCompaniesConnector,
        private readonly runner: TopologyRunner,
    ) {
        super(provider, customWflowGetOrganizationsConnector);
    }

    public getFormStack(): FormStack {
        return super
            .getFormStack()
            .addForm(new Form(FLEXI_BEE_FORM, 'FlexiBee settings'));
    }

    // eslint-disable-next-line @typescript-eslint/no-misused-promises, @typescript-eslint/strict-void-return
    public async syncAfterUninstallCallback(req: Request): Promise<void> {
        await this.syncAfterDisableCallback(req);
    }

    // eslint-disable-next-line @typescript-eslint/no-misused-promises, @typescript-eslint/strict-void-return
    public async syncAfterEnableCallback(req: Request): Promise<void> {
        const { user, sdk } = JSON.parse(String(req.body));

        await this.runner.runByName(
            {},
            Topology.WFLOW_TO_FLEXIBEE_WEBHOOKS,
            'subscribe',
            ProcessDto.createForFormRequest(this.getName(), user, sdk, crypto.randomUUID()),
            user,
        );
    }

    // eslint-disable-next-line @typescript-eslint/no-misused-promises, @typescript-eslint/strict-void-return
    public async syncAfterDisableCallback(req: Request): Promise<void> {
        const { user, sdk } = JSON.parse(String(req.body));

        await this.runner.runByName(
            {},
            Topology.WFLOW_TO_FLEXIBEE_WEBHOOKS,
            'unsubscribe',
            ProcessDto.createForFormRequest(this.getName(), user, sdk, crypto.randomUUID()),
            user,
        );
    }

    public getWebhookSubscriptions(): WebhookSubscription[] {
        return [
            new WebhookSubscription(WebhookType.DOCUMENT_READY_TO_EXPORT, 'document-ready-to-export', Topology.WFLOW_TO_FLEXIBEE_FAKTURA_PRIJATA),
        ];
    }

    protected async customFormReplace(formStack: FormStack, applicationInstall: ApplicationInstall): Promise<void> {
        const sdk = applicationInstall.getSdk();
        const organizations = (await this.customWflowGetOrganizationsConnector.processAction(
            ProcessDto.createForFormRequest(
                WFLOW_NAME,
                applicationInstall.getUser(),
                sdk,
                crypto.randomUUID(),
                'form',
            ),
        )).getJsonData();

        const form = formStack.getForms().find((item) => item.getKey() === ORGANIZATION_FORM);
        const settings = applicationInstall.getSettings()[ORGANIZATION_FORM];

        if (!form) {
            return;
        }

        const choices: Record<string, string>[]
            = organizations.map((organization) => ({ [organization.subdomain]: organization.name }));

        form.addField(
            new Field(
                FieldType.SELECT_BOX,
                ORGANIZATION,
                'Organization name',
                settings?.[ORGANIZATION],
                true,
            ).setChoices(choices),
        );

        const [
            wflowGetDocumentTypesConnectorProcessDto,
            flexiBeeGetCompaniesConnectorProcessDto,
        ] = await Promise.all([
            this.wflowGetDocumentTypesConnector.processAction(
                this.createProcessDtoForFormRequest(WFLOW_NAME, applicationInstall.getUser(), sdk),
            ),
            this.flexiBeeGetCompaniesConnector.processAction(
                this.createProcessDtoForFormRequest(FLEXI_BEE_NAME, applicationInstall.getUser(), sdk),
            ),
        ]);

        this.createFlexiBeeForm(
            formStack,
            applicationInstall,
            wflowGetDocumentTypesConnectorProcessDto,
            flexiBeeGetCompaniesConnectorProcessDto,
        );
    }

    private createFlexiBeeForm(
        formStack: FormStack,
        applicationInstall: ApplicationInstall,
        wflowGetDocumentTypesConnectorProcessDto: ProcessDto<IWflowGetDocumentTypesConnectorOutput[]>,
        flexiBeeGetCompaniesConnectorProcessDto: ProcessDto<IFlexiBeeGetCompaniesConnectorOutput[]>,
    ): void {
        const flexiBeeForm = formStack.getForms().find((form) => form.getKey() === FLEXI_BEE_FORM);
        const flexiBeeSettings = applicationInstall.getSettings()[FLEXI_BEE_FORM];

        if (!flexiBeeForm) {
            return;
        }

        const flexiBeeCompanyChoices: Record<string, string>[] = [];

        for (const { dbNazev, nazev } of flexiBeeGetCompaniesConnectorProcessDto.getJsonData()) {
            flexiBeeCompanyChoices.push({ [dbNazev]: nazev });
        }

        flexiBeeCompanyChoices.sort((one, two) => Object.values(one)[0].localeCompare(Object.values(two)[0]));

        for (const { id, name } of wflowGetDocumentTypesConnectorProcessDto.getJsonData()) {
            flexiBeeForm.addField(new Field(
                FieldType.SELECT_BOX,
                id,
                name,
                flexiBeeSettings?.[id],
                true,
            ).setChoices(flexiBeeCompanyChoices));
        }
    }

    private createProcessDtoForFormRequest(applicationName: string, user: string, sdk: string): ProcessDto {
        return ProcessDto.createForFormRequest(applicationName, user, sdk, crypto.randomUUID(), 'form');
    }

}
