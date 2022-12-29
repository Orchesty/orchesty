enum DocumentEnum {
    APPLICATION_INSTALL = 'AppInstall',
    WEBHOOK = 'Webhook',
    NODE = 'Node',
    API_TOKEN = 'ApiToken',
}

export function isDocumentSupported(document: string): boolean {
    return ([
        DocumentEnum.APPLICATION_INSTALL,
        DocumentEnum.WEBHOOK,
        DocumentEnum.NODE,
    ] as string[]).includes(document);
}

export default DocumentEnum;
