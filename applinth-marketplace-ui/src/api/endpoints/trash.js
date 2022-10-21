export default {
  grid: {
    id: 'GET_TRASH_ITEMS',
    urlPattern: '/trash',
    request: ({ paging, sorter }) => ({
      url: `/trash?filter=${JSON.stringify({ paging, sorter })}`,
      method: 'GET',
    }),
  },
  getById: {
    id: 'GET_TRASH_ITEM_DETAIL',
    urlPattern: '/trash/:id',
    request: ({ id }) => ({
      url: `/trash/${id}`,
      method: 'GET',
    }),
  },
  update: {
    id: 'UPDATE_TRASH_ITEM',
    urlPattern: '/trash/:id',
    request: ({ id, body, headers }) => ({
      url: `/trash/${id}`,
      method: 'PUT',
      data: {
        body,
        headers,
      },
    }),
  },
  accept: {
    id: 'ACCEPT_TRASH_ITEM',
    urlPattern: '/trash/:id/accept',
    request: ({ id }) => ({
      url: `/trash/${id}/accept`,
      method: 'POST',
    }),
  },
  acceptAll: {
    id: 'ACCEPT_ALL_TRASH_ITEMS',
    urlPattern: '/trash/accept',
    request: (ids) => ({
      url: `/trash/accept`,
      method: 'POST',
      data: {
        ids,
      },
    }),
  },
  reject: {
    id: 'REJECT_TRASH_ITEM',
    urlPattern: '/trash/:id/reject',
    request: ({ id }) => ({
      url: `/trash/${id}/reject`,
      method: 'POST',
    }),
  },
  rejectAll: {
    id: 'REJECT_ALL_TRASH_ITEMS',
    urlPattern: '/trash/reject',
    request: (ids) => ({
      url: `/trash/reject`,
      method: 'POST',
      data: {
        ids,
      },
    }),
  },
}
