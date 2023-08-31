export default {
  create: {
    id: "JWT_TOKENS_CREATE",
    request: (data) => ({
      url: `/apiTokens`,
      method: "POST",
      data: {
        ...data,
      },
    }),
  },
  delete: {
    id: "JWT_TOKENS_DELETE",
    request: ({ id }) => ({
      url: `/apiTokens/${id}`,
      method: "DELETE",
    }),
  },
  grid: {
    id: "JWT_TOKENS_GRID",
    request: ({ search, filter, paging, sorter }) => ({
      url: `/apiTokens?filter=${JSON.stringify({
        search,
        filter,
        paging,
        sorter,
      })}`,
      method: "GET",
    }),
  },
}
