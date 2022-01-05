import axios from 'axios'

import { config } from '../config'
import { LOCAL_STORAGE } from '../enums'
import { logout, onError, removeError, startSending, stopSending } from './utils'

const graphqlClient = axios.create({
  baseURL: config.backend.graphqlBaseUrl,
  method: 'POST',
  withCredentials: false,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json; charset=utf-8',
  },
})

const send = (config) => {
  const headers = config.headers || {}

  const token = localStorage.getItem(LOCAL_STORAGE.USER_TOKEN)
  if (token) {
    headers['Authorization'] = `${token}`
  }

  return graphqlClient.request({ ...config, headers: { ...headers } })
}

const query =
  (route) =>
  ({ query, variables }) => {
    return send({
      data: JSON.stringify({
        query,
        variables,
      }),
      baseURL: `${config.backend.graphqlBaseUrl}${route}`,
    })
  }

const checkError = (errors, store, id, errorType) => {
  errors.forEach((item) => {
    // logout
    if (item.code === 'UNAUTHORIZED') {
      logout(store.commit, store.dispatch)

      return
    }

    onError(store, id, item.code ? item.code : 'UNKNOWN', errorType)
  })
}

const call = ({ requestData, params = null, throwError = false, store }, sender) => {
  const { id, errorType, loadingType, request, reduce, mock } = requestData

  if (!id) {
    throw new Error('Request must have id.')
  }

  if (mock) {
    // eslint-disable-next-line
    console.log(`App using mock request id=[${id}]`)

    if (reduce) {
      return reduce(mock())
    }

    return mock()
  }

  return new Promise((resolve, reject) => {
    startSending(store.commit, id, errorType, loadingType)

    return sender(request(params))
      .then((res) => {
        stopSending(store.commit, id)
        removeError(store.commit, id)

        // process error on success
        if (res.data.errors && res.data.errors.length > 0) {
          res.data.errors.forEach(() => {
            checkError(res.data.errors, store, id, errorType)
          })

          if (throwError) {
            reject(res.data.errors)
          }

          return
        }

        if (reduce) {
          resolve(reduce(res.data.data))
        }

        resolve(res.data.data)
      })
      .catch((err) => {
        // eslint-disable-next-line
        console.error('Response ERROR!', err)

        if (err.response) {
          // logout
          if (err.response.status && err.response.status === 401) {
            logout(store.commit, store.dispatch)

            return
          }

          // process errors
          if (err.response.data.errors && err.response.data.errors.length > 0) {
            checkError(err.response.data.errors, store, id, errorType)
          }
        } else {
          // unknown error
          onError(store, id, 'UNKNOWN', errorType)
        }

        stopSending(store.commit, id)

        if (throwError) {
          reject(err)
        }
      })
  })
}

const callGraphQL = (params) => call(params, query())

export { callGraphQL }
