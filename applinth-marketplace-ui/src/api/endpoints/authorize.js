export default {
  getAuthorizationApplicationLink(appKey) {
    return `/api/applinth/application/${appKey}/authorize`
  },
  getAuthorizationSettingsLink() {
    return `/api/applinth/settings/authorize`
  },
}
