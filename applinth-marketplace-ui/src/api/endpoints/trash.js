export default {
  grid: {
    id: 'GET_TRASH_ITEMS',
    request: () => {
      return {
        url: `/trash`,
        method: 'GET',
      }
    },
  },
  getById: {
    id: 'GET_TRASH_ITEM_DETAIL',
    request: ({ id }) => {
      return {
        url: `/trash/${id}`,
        method: 'GET',
      }
    },
  },
  update: {
    id: 'UPDATE_TRASH_ITEM',
    request: ({ id, body, headers }) => {
      return {
        url: `/trash/${id}`,
        method: 'PUT',
        data: {
          body,
          headers,
        },
      }
    },
  },
  accept: {
    id: 'ACCEPT_TRASH_ITEM',
    request: ({ id }) => {
      return {
        url: `/trash/${id}/accept`,
        method: 'GET',
      }
    },
  },
  acceptAll: {
    id: 'ACCEPT_ALL_TRASH_ITEMS',
    request: ({ ids }) => {
      return {
        url: `/trash/accept`,
        method: 'POST',
        data: {
          ids,
        },
      }
    },
  },
  reject: {
    id: 'REJECT_TRASH_ITEM',
    request: ({ id }) => {
      return {
        url: `/trash/${id}/reject`,
        method: 'GET',
      }
    },
  },
  rejectAll: {
    id: 'REJECT_ALL_TRASH_ITEMS',
    request: (ids) => {
      return {
        url: `/trash/reject`,
        method: 'POST',
        data: ids,
      }
    },
  },
}
