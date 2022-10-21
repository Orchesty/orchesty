enum OrchestyPageEnum {
    NEWSLETTER = 0,
    COMMUNITY = 1,
    SALES = 2,
    CONTACT = 3,
}

function getOrchestyPageName(emailTemplate: OrchestyPageEnum): string {
    switch (emailTemplate) {
        case OrchestyPageEnum.NEWSLETTER:
            return 'newsletter';
        case OrchestyPageEnum.COMMUNITY:
            return 'community';
        case OrchestyPageEnum.SALES:
            return 'sales';
        case OrchestyPageEnum.CONTACT:
            return 'contact';
        default:
            throw Error(`Unknown email template [${emailTemplate}]`);
    }
}

export { getOrchestyPageName, OrchestyPageEnum };
