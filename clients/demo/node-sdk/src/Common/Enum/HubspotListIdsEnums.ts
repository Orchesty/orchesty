enum HubspotListIdsEnums {
    NEWSLETTER = 39,
    COMMUNITY = 63,
    SALES = 64,
    CONTACT_FROM = 65,
    CONTACT_APPLINTH = -1,
    WHITE_PAPER_APPLINTH = -2,
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
      return 'contact-from';
    case HubspotListIdsEnums.CONTACT_APPLINTH:
      return 'contact-applinth';
    case HubspotListIdsEnums.WHITE_PAPER_APPLINTH:
      return 'white-paper-applinth';
    default:
      throw Error(`Unknown hubSpot list [${hubSpotList}]`);
  }
}

export { getHubspotListName, HubspotListIdsEnums };
