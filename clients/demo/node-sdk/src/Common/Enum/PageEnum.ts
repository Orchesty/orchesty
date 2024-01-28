enum PageEnum {
    NEWSLETTER = 0,
    COMMUNITY = 1,
    SALES = 2,
    CONTACT = 3,
    WHITE_PAPER = 4,
}

function getPageName(emailTemplate: PageEnum): string {
    switch (emailTemplate) {
        case PageEnum.NEWSLETTER:
            return 'newsletter';
        case PageEnum.COMMUNITY:
            return 'community';
        case PageEnum.SALES:
            return 'sales';
        case PageEnum.CONTACT:
            return 'contact';
        case PageEnum.WHITE_PAPER:
            return 'white-paper';
        default:
            throw Error(`Unknown email template [${emailTemplate}]`);
    }
}

export { getPageName, PageEnum };
