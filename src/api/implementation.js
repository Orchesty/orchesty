export default {
  grid: {
    id: 'IMPLEMENTATION_GRID',
    request: (data) => ({
      url: `/sdks?filter=${JSON.stringify(data)}`,
      method: 'GET',
    }),
  },
  getList: {
    id: 'IMPLEMENTATION_GET_LIST',
    request: () => ({
      url: `/sdks`,
      method: 'GET',
    }),
  },
  getById: {
    id: 'IMPLEMENTATION_GET_BY_ID',
    request: ({ id }) => ({
      url: `/sdks/${id}`,
      method: 'GET',
    }),
  },
  create: {
    id: 'IMPLEMENTATION_CREATE',
    request: ({ name, site }) => ({
      url: `/sdks`,
      method: 'POST',
      data: {
        name: name,
        url: site,
      },
    }),
  },
  update: {
    id: 'IMPLEMENTATION_UPDATE',
    request: ({ name, site, id }) => ({
      url: `/sdks/${id}`,
      method: 'PUT',
      data: {
        name: name,
        url: site,
        id: id,
      },
    }),
  },
  delete: {
    id: 'IMPLEMENTATION_DELETE',
    request: ({ id }) => ({
      url: `/sdks/${id}`,
      method: 'DELETE',
      data: {
        id: id,
      },
    }),
  },
}
