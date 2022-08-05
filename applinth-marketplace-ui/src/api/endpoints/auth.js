export default {
  initialAuth: {
    id: 'INITIAL_AUTH',
    request: ({ initialToken }) => ({
      url: `/authorization/login`,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json; charset=utf-8',
        Authorization: initialToken,
      },
      method: 'POST',
    }),
  },
  refreshAuth: {
    id: 'refreshAuth',
    request: () => ({
      url: `/authorization/logged`,
      method: 'GET',
    }),
  },
}
