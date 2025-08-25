export default {
  getAuthorizationApplicationLink(appKey, sdk) {
    return `/api/applinth/application/${appKey}/authorize?sdk=${sdk}`
  },
  getAuthorizationSettingsLink(sdk) {
    return `/api/applinth/settings/authorize?sdk=${sdk}`
  },
}
