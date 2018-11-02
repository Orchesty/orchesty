export default {
  notificationTimeout: 6000,
  defaultPageSize: 20,
  allowChangeServer: false,
  urlPrefix: null,
  preferPaging: false,
  mainPage: 'log_list',
  clearGeneralSearch: false,
  metricsRefreshInterval: null, // null/false - turn off; number - refresh interval in ms
  hotKeys: {
    generalSearch: {   // false - for turn off
      alt: false,
      ctrl: true,
      shift: false,
      char: 'g'
    }
  }
}