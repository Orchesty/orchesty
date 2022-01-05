export default {
  getList: {
    id: 'FOLDER_GET_LIST',
    request: () => ({
      url: '/categories',
      method: 'GET',
    }),
  },
  create: {
    id: 'FOLDER_CREATE',
    request: ({ name, parent }) => ({
      url: '/categories',
      method: 'POST',
      data: {
        name: name,
        parent: parent || null,
      },
    }),
  },
  edit: {
    id: 'FOLDER_EDIT',
    request: ({ id, name, parent }) => ({
      url: `/categories/${id}`,
      method: 'PUT',
      data: {
        name: name,
        parent: parent || null,
      },
    }),
  },
  delete: {
    id: 'FOLDER_DELETE',
    request: ({ id }) => ({
      url: `/categories/${id}`,
      method: 'DELETE',
    }),
  },
}
