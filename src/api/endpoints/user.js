export default {
  getById: {
    id: 'USER_GET_BY_ID',
    request: ({ id }) => ({
      url: `/user/${id}`,
      method: 'GET',
    }),
  },
  create: {
    id: 'USER_REGISTER',
    request: ({ email }) => ({
      url: `/user/register`,
      method: 'POST',
      data: {
        email,
      },
    }),
  },
  update: {
    id: 'USER_UPDATE',
    request: ({ id, data }) => ({
      url: `/user/${id}/saveSettings`,
      method: 'POST',
      data: {
        ...data,
      },
    }),
  },
  delete: {
    id: 'USER_DELETE',
    request: ({ id }) => ({
      url: `/user/${id}/delete`,
      method: 'DELETE',
    }),
  },
  getList: {
    id: 'USER_GET_LIST',
    request: (data) => ({
      url: `/user/list?filter=${JSON.stringify(data)}`,
      method: 'POST',
    }),
  },
}
