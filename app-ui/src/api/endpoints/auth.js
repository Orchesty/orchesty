export default {
  login: {
    id: 'AUTH_LOGIN',
    request: ({ email, password }) => ({
      url: '/user/login',
      method: 'POST',
      data: {
        email,
        password,
      },
    }),
  },
  checkLogged: {
    id: 'AUTH_CHECK_LOGGED',
    request: () => ({
      url: '/user/check_logged',
      method: 'GET',
      withCredentials: true,
    }),
  },
  logout: {
    id: 'AUTH_LOGOUT',
    request: () => ({
      url: '/user/logout',
      method: 'POST',
    }),
  },
  forgotPassword: {
    id: 'AUTH_FORGOT_PASSWORD',
    request: ({ email }) => ({
      url: '/user/reset_password',
      method: 'POST',
      data: {
        email,
      },
    }),
  },
  checkToken: {
    id: 'AUTH_CHECK_TOKEN',
    request: ({ token }) => ({
      url: `/user/${token}/verify`,
      method: 'POST',
    }),
  },
  checkRegisterToken: {
    id: 'AUTH_CHECK_REGISTER_TOKEN',
    request: ({ token }) => ({
      url: `/user/${token}/activate`,
      method: 'POST',
    }),
  },
  setPassword: {
    id: 'AUTH_SET_PASSWORD',
    request: ({ token, password }) => ({
      url: `/user/${token}/set_password`,
      method: 'POST',
      data: {
        password,
      },
    }),
  },
  changePassword: {
    id: 'AUTH_USER_CHANGE_PASSWORD',
    request: (data) => ({
      url: '/user/change_password',
      method: 'POST',
      data,
    }),
  },
}
