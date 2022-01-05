export default {
  grid: {
    id: 'NOTIFICATION_GRID',
    request: () => ({
      url: `/notification_settings`,
      method: 'GET',
    }),
  },
  update: {
    id: 'NOTIFICATION_UPDATE',
    request: (data) => ({
      url: `/notification_settings/${data.id}`,
      method: 'PUT',
      data: data.settings,
    }),
  },
  events: {
    id: 'NOTIFICATION_GET_EVENT_LIST',
    request: () => ({
      url: `/notification_settings/events`,
      method: 'GET',
    }),
    reduce: (data) => {
      const res = []

      Object.keys(data.items).forEach((key) => {
        res.push({
          key,
          title: data.items[key],
        })
      })
      return {
        items: res,
        paging: {
          ...data.paging,
        },
        filter: data.filter,
        sorter: data.sorter,
      }
    },
  },
}
