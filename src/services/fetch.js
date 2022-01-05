import axios from 'axios'

import { config } from '../config'
import { LOCAL_STORAGE } from '../enums'
import { logout, onError, removeError, startSending, stopSending } from './utils'

const apiClient = axios.create({
  baseURL: `${config.backend.apiBaseUrl}/api`,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json; charset=utf-8',
  },
})

const send = (config) => {
  const { method, body, url } = config

  const headers = config.headers || {}

  return new Promise((resolve, reject) => {
    if (!method) {
      reject(new Error('The request must have method.'))
    }

    if (!url) {
      reject(new Error('The request must have url.'))
    }

    const token = localStorage.getItem(LOCAL_STORAGE.USER_TOKEN)
    if (token) {
      headers['Authorization'] = `${token}`
    }

    resolve(apiClient.request({ ...config, url, headers: { ...headers }, body: JSON.stringify(body) }))
  })
}

const call = ({ requestData, params = null, throwError = false, store }, sender) => {
  const { id, errorType, loadingType, request, mock, reduce } = requestData

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

        if (reduce) {
          resolve(reduce(res.data))
        }

        resolve(res.data)
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
          // process error
          onError(
            store,
            id,
            err.response.data.status ? err.response.data.status : 'UNKNOWN',
            errorType,
            err.response.data.message
          )
        } else {
          // unknown error
          onError(store, id, 'UNKNOWN', errorType, err.response.data.message)
        }

        stopSending(store.commit, id)

        if (throwError) {
          reject(err)
        }
      })
  })
}

const callApi = (params) => call(params, (config) => send({ ...config, withCredentials: true }))

export { callApi }
