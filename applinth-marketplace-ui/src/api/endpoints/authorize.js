export default {
  getAuthorizationApplicationLink(appKey) {
    return `/api/applinth/applications/${appKey}/authorize`
  },
  getAuthorizationSettingsLink() {
    return `/api/applinth/settings/authorize`
  },
}
