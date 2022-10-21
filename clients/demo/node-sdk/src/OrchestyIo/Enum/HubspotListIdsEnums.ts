enum HubspotListIdsEnums {
    NEWSLETTER = 39,
    COMMUNITY = 63,
    SALES = 64,
    CONTACT_FROM = 65,
}

function getHubspotListName(hubSpotList: HubspotListIdsEnums): string {
    switch (hubSpotList) {
        case HubspotListIdsEnums.NEWSLETTER:
            return 'newsletter';
        case HubspotListIdsEnums.COMMUNITY:
            return 'community';
        case HubspotListIdsEnums.SALES:
            return 'sales';
        case HubspotListIdsEnums.CONTACT_FROM:
            return 'contact_from';
        default:
            throw Error(`Unknown hubSpot list [${hubSpotList}]`);
    }
}

export { getHubspotListName, HubspotListIdsEnums };
