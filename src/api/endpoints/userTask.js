export default {
  grid: {
    id: 'USER_TASKS_GRID',
    request: ({ filter, params, paging, search, sorter }) => {
      filter.forEach((conditionsArray) => {
        conditionsArray.forEach((condition) => {
          if (condition.column === 'topologyId') {
            condition.value = [params.id]
          }
        })
      })
      let data = {
        filter,
        paging,
        search,
        sorter,
      }
      return {
        url: `/user-task?filter=${JSON.stringify(data)}`,
        method: 'GET',
      }
    },
  },
  getById: {
    id: 'USER_TASK_GET_BY_ID',
    request: ({ id }) => {
      return {
        url: `/user-task/${id}`,
        method: 'GET',
      }
    },
  },
  update: {
    id: 'USER_TASK_UPDATE',
    request: ({ id, body, headers }) => {
      return {
        url: `/user-task/${id}`,
        method: 'PUT',
        data: {
          body,
          headers,
        },
      }
    },
  },
  accept: {
    id: 'USER_TASK_ACCEPT',
    request: ({ id }) => {
      return {
        url: `/user-task/${id}/accept`,
        method: 'POST',
      }
    },
  },
  acceptAll: {
    id: 'USER_TASK_ACCEPT_ALL',
    request: ({ ids }) => {
      return {
        url: `/user-task/accept`,
        method: 'POST',
        data: {
          ids,
        },
      }
    },
  },
  reject: {
    id: 'USER_TASK_REJECT',
    request: ({ id }) => {
      return {
        url: `/user-task/${id}/reject`,
        method: 'POST',
      }
    },
  },
  rejectAll: {
    id: 'USER_TASK_REJECT_ALL',
    request: ({ ids }) => {
      return {
        url: `/user-task/reject`,
        method: 'POST',
        data: {
          ids,
        },
      }
    },
  },
}
